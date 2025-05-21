<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class JsonProcessorService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly EntityManagerInterface $entityManager,
        private readonly EntityTypeDeterminer $typeDeterminer,
        private readonly EntityFieldMapper $fieldMapper,
        private readonly EntityValidator $validator,
        private readonly AppointmentCreator $appointmentCreator,
        #[Autowire('@monolog.logger.entity')]
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Récupère et traite un fichier JSON depuis une URL
     */
    public function processJsonFromUrl(string $url): array
    {
        try {
            $this->logger->info('Début du traitement JSON depuis l\'URL: {url}', ['url' => $url]);
            
            $response = $this->httpClient->request('GET', $url);
            
            if ($response->getStatusCode() !== Response::HTTP_OK) {
                throw new HttpException(
                    $response->getStatusCode(),
                    'Erreur lors de la récupération du fichier JSON'
                );
            }

            $data = json_decode($response->getContent(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new HttpException(
                    Response::HTTP_BAD_REQUEST,
                    'Le contenu n\'est pas un JSON valide: ' . json_last_error_msg()
                );
            }

            return $data;
        } catch (HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new HttpException(
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'Erreur lors du traitement du JSON: ' . $e->getMessage()
            );
        }
    }

    /**
     * Vérifie si les données sont une entité valide à traiter
     */
    private function isValidEntityData(array $item): bool
    {
        // Liste des champs à ignorer car ils viennent de l'utilisateur connecté
        $ignoredFields = ['full_name', 'address', 'phone'];

        // Ignorer les données qui ne contiennent que des champs à ignorer
        $hasValidFields = false;
        foreach ($item as $key => $value) {
            if (!in_array($key, $ignoredFields)) {
                $hasValidFields = true;
                break;
            }
        }

        if (!$hasValidFields) {
            $this->logger->debug('Données ignorées car ne contiennent que des champs utilisateur: {data}', ['data' => $item]);
            return false;
        }

        return true;
    }

    /**
     * Récupère le mapping des champs pour un type d'entité spécifique
     */
    private function getFieldMappingForType(string $entityType): array
    {
        return match($entityType) {
            'dealership' => [
                'dealership_name' => 'name',
                'city' => 'city',
                'address' => 'address',
                'zipcode' => 'zipcode',
                'latitude' => 'latitude',
                'longitude' => 'longitude'
            ],
            'vehicle' => [
                'brand' => 'brand',
                'model' => 'model',
                'year' => 'year',
                'price' => 'price'
            ],
            'service' => [
                'operation_name' => 'name',
                'category' => 'category',
                'additionnal_help' => 'help',
                'additionnal_comment' => 'comment',
                'time_unit' => 'time',
                'price' => 'price'
            ],
            'client' => [
                'full_name' => 'name',
                'address' => 'address',
                'phone' => 'phone'
            ],
            default => []
        };
    }

    /**
     * Traite et sauvegarde les données JSON en base de données
     */
    public function processAndSaveData(array $data, string $entityClass, array $fieldMapping, array $options = []): array
    {
        $this->logger->info('Début du traitement des données JSON', ['data' => $data]);
        
        if (!isset($options['user_id'])) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'L\'ID de l\'utilisateur est requis');
        }

        try {
            $this->entityManager->beginTransaction();
            
            // Filtrer les données valides - s'assurer que nous ne traitons que des tableaux
            $validData = array_filter($data, function($item) {
                return is_array($item) && $this->validator->isValidEntityData($item);
            });
            
            if (empty($validData)) {
                throw new HttpException(
                    Response::HTTP_BAD_REQUEST,
                    'Aucune donnée valide trouvée dans le JSON'
                );
            }
            
            // Récupérer le client via l'utilisateur
            $client = $this->validator->validateUser($options['user_id']);
            $relatedEntities['client'] = $client;

            // Récupérer le véhicule
            if (!isset($data['car_immatriculation'])) {
                throw new HttpException(Response::HTTP_BAD_REQUEST, 'L\'immatriculation du véhicule est requise');
            }
            $relatedEntities['vehicle'] = $this->validator->validateVehicle($client, $data['car_immatriculation']);

            // Traiter les autres entités
            foreach ($validData as $item) {
                $entityType = $this->typeDeterminer->determineType($item);
                if (!$entityType || $entityType === 'client') {
                    continue;
                }

                $criteria = $this->getSearchCriteria($entityType, $item);
                $relatedEntities[$entityType] = $this->validator->validateExistingEntity($entityType, $criteria);
            }

            // Créer le rendez-vous avec les données du rendez-vous
            $appointment = $this->appointmentCreator->create($relatedEntities, $data);
            
            $this->entityManager->flush();
            $this->entityManager->commit();

            return [$appointment];
        } catch (HttpException $e) {
            $this->rollbackTransaction();
            throw $e;
        } catch (\Exception $e) {
            $this->rollbackTransaction();
            throw new HttpException(
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'Erreur lors de la sauvegarde des données: ' . $e->getMessage()
            );
        }
    }

    private function getSearchCriteria(string $entityType, array $item): array
    {
        return match($entityType) {
            'dealership' => ['name' => $item['dealership_name']],
            'service' => ['name' => $item['operation_name']],
            default => throw new \InvalidArgumentException("Type d'entité non supporté: $entityType")
        };
    }

    private function rollbackTransaction(): void
    {
        if ($this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->rollback();
        }
    }

    /**
     * Détermine le type d'entité basé sur les clés présentes dans l'élément
     */
    private function determineEntityType(array $item, array $fieldMapping): ?string
    {
        // Ignorer la clé car_immatriculation car elle est traitée séparément
        if (isset($item['car_immatriculation'])) {
            unset($item['car_immatriculation']);
        }

        // Définition des champs caractéristiques pour chaque type d'entité
        $entitySignatures = [
            'dealership' => ['dealership_name', 'city', 'address', 'zipcode', 'latitude', 'longitude'],
            'vehicle' => ['brand', 'model', 'year', 'price'],
            'service' => ['operation_name', 'category', 'time_unit', 'price'],
            'appointment' => ['preferred_datetime']
        ];

        // Vérifier chaque type d'entité
        foreach ($entitySignatures as $type => $signatureFields) {
            $matchCount = 0;
            foreach ($signatureFields as $field) {
                if (isset($item[$field])) {
                    $matchCount++;
                }
            }
            
            // Si plus de 50% des champs caractéristiques sont présents, on considère que c'est ce type d'entité
            if ($matchCount >= count($signatureFields) * 0.7) {
                $this->logger->debug('Type d\'entité déterminé: {type} avec {count} champs correspondants', [
                    'type' => $type,
                    'count' => $matchCount,
                    'fields' => array_keys($item)
                ]);
                return $type;
            }
        }

        $this->logger->warning('Type d\'entité non déterminé pour: {data}', [
            'data' => $item,
            'available_fields' => array_keys($item)
        ]);
        return null;
    }

    /**
     * Flush et clear l'EntityManager avec gestion des erreurs
     */
    private function flushAndClear(int $count): void
    {
        try {
            $this->entityManager->flush();
            $this->entityManager->clear();
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du flush: {error}', [
                'error' => $e->getMessage(),
                'count' => $count
            ]);
            throw $e;
        }
    }
} 