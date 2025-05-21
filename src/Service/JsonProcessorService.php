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
    private $httpClient;
    private $entityManager;
    private $propertyAccessor;
    private $cache;

    public function __construct(
        HttpClientInterface $httpClient, 
        EntityManagerInterface $entityManager,
        CacheInterface $cache,
        #[Autowire('@monolog.logger.entity')]
        private readonly LoggerInterface $logger
    ) 
    {
        $this->httpClient = $httpClient;
        $this->entityManager = $entityManager;
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->cache = $cache;
    }

    /**
     * Récupère et traite un fichier JSON depuis une URL
     *
     * @param string $url L'URL du fichier JSON
     * @return array Les données JSON traitées
     * @throws HttpException Si la requête échoue ou si le JSON est invalide
     */
    public function processJsonFromUrl(string $url): array
    {
        try {
            $this->logger->info('Début du traitement JSON depuis l\'URL: {url}', ['url' => $url]);
            
            $response = $this->httpClient->request('GET', $url);
            
            if ($response->getStatusCode() !== Response::HTTP_OK) {
                $this->logger->error('Erreur HTTP lors de la récupération du JSON: {status}', [
                    'status' => $response->getStatusCode()
                ]);
                throw new HttpException(
                    $response->getStatusCode(),
                    'Erreur lors de la récupération du fichier JSON'
                );
            }

            $content = $response->getContent();
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('JSON invalide: {error}', [
                    'error' => json_last_error_msg()
                ]);
                throw new HttpException(
                    Response::HTTP_BAD_REQUEST,
                    'Le contenu n\'est pas un JSON valide: ' . json_last_error_msg()
                );
            }

            $this->logger->info('JSON traité avec succès: {count} éléments', [
                'count' => count($data)
            ]);

            return $data;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du traitement du JSON: {error}', [
                'error' => $e->getMessage()
            ]);
            throw new HttpException(
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'Erreur lors du traitement du JSON: ' . $e->getMessage()
            );
        }
    }

    /**
     * Récupère une valeur dans un tableau en utilisant un chemin avec des points
     * 
     * @param mixed $value La valeur à traiter (peut être un tableau, une chaîne, ou tout autre type)
     * @param string $path Le chemin à suivre pour accéder à la valeur
     * @return mixed La valeur trouvée ou la valeur d'origine si ce n'est pas un tableau
     */
    private function getNestedValue(mixed $value, string $path): mixed
    {
        // Si la valeur n'est pas un tableau, on la retourne directement
        if (!is_array($value)) {
            $this->logger->debug('Valeur non-tableau retournée directement', [
                'value_type' => gettype($value),
                'value' => $value,
                'path' => $path
            ]);
            return $value;
        }

        // Si le chemin est vide, retourner la valeur complète
        if (empty($path)) {
            return $value;
        }

        $keys = explode('.', $path);
        $current = $value;

        foreach ($keys as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                $this->logger->debug('Clé non trouvée dans le chemin', [
                    'key' => $key,
                    'path' => $path,
                    'current_type' => gettype($current),
                    'current' => $current
                ]);
                return null;
            }
            $current = $current[$key];
        }

        return $current;
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
        $this->logger->info('Début du traitement des données JSON', [
            'data' => $data
        ]);
        
        $entities = [];
        $batchSize = $options['batchSize'] ?? 100;
        $count = 0;

        if (!class_exists($entityClass)) {
            $this->logger->error('Classe invalide: {class}', ['class' => $entityClass]);
            throw new HttpException(
                Response::HTTP_BAD_REQUEST,
                sprintf('La classe "%s" n\'existe pas', $entityClass)
            );
        }

        try {
            // Démarrer une nouvelle transaction
            $this->entityManager->beginTransaction();
            
            $metadata = $this->getCachedMetadata($entityClass);
            $relations = $metadata->getAssociationMappings();

            $this->logger->info('Début du traitement de {count} éléments pour l\'entité {entity}', [
                'count' => count($data),
                'entity' => $entityClass
            ]);

            // Filtrer les données pour ne garder que les entités valides
            $validData = array_filter($data, function($item) {
                return is_array($item) && $this->isValidEntityData($item);
            });

            $this->logger->info('{count} entités valides trouvées après filtrage', [
                'count' => count($validData),
                'validData' => $validData
            ]);

            // D'abord, trouver le Client via l'utilisateur connecté
            if (!isset($options['user_id'])) {
                $this->logger->error('ID utilisateur manquant dans les options');
                throw new HttpException(
                    Response::HTTP_BAD_REQUEST,
                    'L\'ID de l\'utilisateur est requis pour trouver le Client'
                );
            }

            $this->logger->info('Recherche de l\'utilisateur connecté', [
                'user_id' => $options['user_id']
            ]);

            // Récupérer l'utilisateur
            $user = $this->entityManager->getRepository('App\\Entity\\User')->find($options['user_id']);
            if (!$user) {
                $this->logger->error('Utilisateur non trouvé: {user_id}', [
                    'user_id' => $options['user_id']
                ]);
                throw new HttpException(
                    Response::HTTP_NOT_FOUND,
                    'Utilisateur non trouvé'
                );
            }

            $this->logger->info('Utilisateur trouvé', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles()
            ]);

            // Récupérer le Client via la méthode getClient() de l'utilisateur
            $client = $user->getClient();
            if (!$client) {
                $this->logger->error('Aucun Client trouvé pour l\'utilisateur {user_id}', [
                    'user_id' => $user->getId()
                ]);
                throw new HttpException(
                    Response::HTTP_NOT_FOUND,
                    'Aucun Client trouvé pour cet utilisateur'
                );
            }

            $this->logger->info('Client trouvé via getClient()', [
                'client_id' => $client->getId(),
                'client_name' => $client->getName(),
                'client_phone' => $client->getPhone(),
                'client_address' => $client->getAddress(),
                'user_id' => $user->getId(),
                'user_email' => $user->getEmail()
            ]);

            // Stocker le Client dans les entités liées
            $relatedEntities['client'] = $client;

            // Rechercher le véhicule du client via l'immatriculation
            $immatriculation = $data['car_immatriculation'];
            

            if (!$immatriculation) {
                $this->logger->error('Immatriculation manquante dans les données', [
                    'data' => $validData
                ]);
                throw new HttpException(
                    Response::HTTP_BAD_REQUEST,
                    'L\'immatriculation du véhicule est requise'
                );
            }

            $this->logger->info('Recherche du véhicule avec l\'immatriculation: {immatriculation}', [
                'immatriculation' => $immatriculation
            ]);

            // Récupérer tous les véhicules du client
            $clientVehicles = $client->getVehicules();
            $this->logger->info('Recherche parmi {count} véhicules du client', [
                'count' => $clientVehicles->count()
            ]);

            // Chercher le véhicule avec l'immatriculation correspondante
            $vehicle = null;
            foreach ($clientVehicles as $v) {
                if ($v->getRegistration() === $immatriculation) {
                    $vehicle = $v;
                    break;
                }
            }

            if (!$vehicle) {
                $this->logger->error('Véhicule non trouvé pour le client', [
                    'client_id' => $client->getId(),
                    'immatriculation' => $immatriculation
                ]);
                throw new HttpException(
                    Response::HTTP_NOT_FOUND,
                    sprintf('Aucun véhicule trouvé avec l\'immatriculation %s pour ce client', $immatriculation)
                );
            }

            $this->logger->info('Véhicule trouvé pour le client', [
                'vehicle_id' => $vehicle->getId(),
                'immatriculation' => $vehicle->getRegistration(),
                'brand' => $vehicle->getBrand(),
                'model' => $vehicle->getModel(),
                'client_id' => $client->getId()
            ]);

            // Stocker le véhicule dans les entités liées
            $relatedEntities['vehicle'] = $vehicle;

            // Maintenant, traiter les autres entités
            $entityData = [];
            foreach ($validData as $item) {
                $entityType = $this->determineEntityType($item, $fieldMapping);
                if ($entityType && $entityType !== 'client') { // On ignore le client car déjà traité
                    $entityData[$entityType][] = $item;
                    $this->logger->debug('Donnée classée dans le type {type}: {data}', [
                        'type' => $entityType,
                        'data' => $item
                    ]);
                }
            }

            $this->logger->info('Données regroupées par type: {types}', [
                'types' => array_keys($entityData)
            ]);

            // Traiter les autres entités (dealership, vehicle, service)
            foreach ($entityData as $entityType => $items) {
                if ($entityType === 'appointment') {
                    continue; // On traitera les rendez-vous en dernier
                }

                $this->logger->info('Recherche des entités de type {type}', [
                    'type' => $entityType
                ]);

                // Déterminer la classe d'entité appropriée
                $targetEntityClass = match($entityType) {
                    'dealership' => 'App\\Entity\\Dealership',
                    'service' => 'App\\Entity\\Service',
                    default => $entityClass
                };

                foreach ($items as $item) {
                    try {
                        // Rechercher l'entité existante
                        $existingEntity = null;
                        if ($entityType === 'dealership') {
                            $existingEntity = $this->entityManager->getRepository($targetEntityClass)
                                ->findOneBy(['name' => $item['dealership_name']]);
                        } elseif ($entityType === 'service') {
                            $this->logger->info('Recherche du service', [
                                'operation_name' => $item['operation_name']
                            ]);
                            $existingEntity = $this->entityManager->getRepository($targetEntityClass)
                                ->findOneBy(['name' => $item['operation_name']]);
                            $this->logger->info('Service trouvé', [
                                'service_id' => $existingEntity ? $existingEntity->getId() : 'non trouvé',
                                'service_name' => $existingEntity ? $existingEntity->getName() : 'non trouvé'
                            ]);
                        }

                        if (!$existingEntity) {
                            $this->logger->error('Entité {type} non trouvée: {data}', [
                                'type' => $entityType,
                                'data' => $item
                            ]);
                            throw new HttpException(
                                Response::HTTP_NOT_FOUND,
                                sprintf('L\'entité %s n\'existe pas dans la base de données', $entityType)
                            );
                        }

                        $this->logger->info('Entité {type} trouvée: {id}', [
                            'type' => $entityType,
                            'id' => $existingEntity->getId()
                        ]);
                        $relatedEntities[$entityType] = $existingEntity;

                    } catch (HttpException $e) {
                        throw $e;
                    } catch (\Exception $e) {
                        $this->logger->error('Erreur lors de la recherche de l\'entité {type}: {error}', [
                            'type' => $entityType,
                            'error' => $e->getMessage(),
                            'item' => $item
                        ]);
                        throw new HttpException(
                            Response::HTTP_INTERNAL_SERVER_ERROR,
                            sprintf('Erreur lors de la recherche de l\'entité %s: %s', $entityType, $e->getMessage())
                        );
                    }
                }
            }

            // Maintenant, créer l'Appointment avec les entités liées
            try {
                $this->logger->info('Vérification des entités liées avant création du rendez-vous', [
                    'related_entities' => array_keys($relatedEntities),
                    'client' => isset($relatedEntities['client']) ? $relatedEntities['client']->getId() : 'non trouvé',
                    'vehicle' => isset($relatedEntities['vehicle']) ? $relatedEntities['vehicle']->getId() : 'non trouvé',
                    'dealership' => isset($relatedEntities['dealership']) ? $relatedEntities['dealership']->getId() : 'non trouvé',
                    'service' => isset($relatedEntities['service']) ? $relatedEntities['service']->getId() : 'non trouvé'
                ]);

                $this->logger->info('Début de la création d\'un nouveau rendez-vous');
                
                $appointment = new $entityClass();
                
                // Vérifier que toutes les entités requises sont présentes
                $requiredEntities = ['dealership', 'vehicle', 'client', 'service'];
                $missingEntities = array_diff($requiredEntities, array_keys($relatedEntities));
                
                if (!empty($missingEntities)) {
                    $this->logger->error('Entités requises manquantes pour le rendez-vous: {missing}', [
                        'missing' => implode(', ', $missingEntities)
                    ]);
                    throw new HttpException(
                        Response::HTTP_BAD_REQUEST,
                        sprintf('Entités requises manquantes: %s', implode(', ', $missingEntities))
                    );
                }

                $this->logger->info('Toutes les entités requises sont présentes', [
                    'dealership_id' => $relatedEntities['dealership']->getId(),
                    'vehicle_id' => $relatedEntities['vehicle']->getId(),
                    'client_id' => $relatedEntities['client']->getId(),
                    'service_id' => $relatedEntities['service']->getId()
                ]);

                // Définir les relations
                $appointment->setDealership($relatedEntities['dealership']);
                $appointment->setVehicule($relatedEntities['vehicle']);
                $appointment->setClient($relatedEntities['client']);
                $appointment->addService($relatedEntities['service']);

                // Définir la date
                try {
                    $date = new \DateTime();
                    $appointment->setDate($date);
                    $this->logger->info('Date du rendez-vous définie: {date}', [
                        'date' => $date->format('Y-m-d H:i:s')
                    ]);
                } catch (\Exception $e) {
                    $this->logger->error('Erreur lors de la définition de la date: {error}', [
                        'error' => $e->getMessage()
                    ]);
                    throw new HttpException(
                        Response::HTTP_INTERNAL_SERVER_ERROR,
                        'Erreur lors de la définition de la date du rendez-vous'
                    );
                }

                $this->entityManager->persist($appointment);
                $entities[] = $appointment;
                
                $this->logger->info('Rendez-vous créé avec succès');

            } catch (HttpException $e) {
                throw $e;
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la création du rendez-vous: {error}', [
                    'error' => $e->getMessage()
                ]);
                throw new HttpException(
                    Response::HTTP_INTERNAL_SERVER_ERROR,
                    sprintf('Erreur lors de la création du rendez-vous: %s', $e->getMessage())
                );
            }

            $this->flushAndClear($count);
        
            // Valider la transaction
            $this->entityManager->commit();

            $this->logger->info('Traitement terminé: {count} entités créées au total', [
                'count' => count($entities)
            ]);

            return $entities;
        } catch (HttpException $e) {
            // Vérifier si une transaction est active avant de faire le rollback
            if ($this->entityManager->getConnection()->isTransactionActive()) {
                $this->entityManager->rollback();
            }
            throw $e;
        } catch (\Exception $e) {
            // Vérifier si une transaction est active avant de faire le rollback
            if ($this->entityManager->getConnection()->isTransactionActive()) {
                $this->entityManager->rollback();
            }
            
            $this->logger->error('Erreur lors de la sauvegarde des données: {error}', [
                'error' => $e->getMessage()
            ]);
            throw new HttpException(
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'Erreur lors de la sauvegarde des données: ' . $e->getMessage()
            );
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
            'appointment' => ['preferred_date']
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
     * Récupère les champs pertinents pour un type d'entité donné
     */
    private function getRelevantFields(array $item, array $fieldMapping): array
    {
        $relevantFields = [];
        
        // Vérifier les clés de premier niveau
        foreach ($item as $key => $value) {
            if (isset($fieldMapping[$key])) {
                $relevantFields[$key] = $fieldMapping[$key];
            }
        }

        // Vérifier les clés imbriquées
        foreach ($fieldMapping as $jsonKey => $entityProperty) {
            $keys = explode('.', $jsonKey);
            if (isset($item[$keys[0]])) {
                $relevantFields[$jsonKey] = $entityProperty;
            }
        }

        return $relevantFields;
    }

    /**
     * Récupère les métadonnées d'une entité avec cache
     */
    private function getCachedMetadata(string $entityClass): ClassMetadata
    {
        $cacheKey = 'entity_metadata_' . str_replace('\\', '_', $entityClass);
        
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($entityClass) {
            $item->expiresAfter(3600); // Cache pour 1 heure
            return $this->entityManager->getClassMetadata($entityClass);
        });
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

    /**
     * Gère les relations entre entités
     *
     * @param object $entity L'entité principale
     * @param string $relationName Le nom de la relation
     * @param mixed $relationData Les données de la relation
     * @param array $relationMapping Les métadonnées de la relation
     * @param array $options Options supplémentaires
     */
    private function handleRelation(object $entity, string $relationName, mixed $relationData, array $relationMapping, array $options): void 
    {
        if (empty($relationData)) {
            return;
        }

        $targetEntity = $relationMapping['targetEntity'];
        $type = $relationMapping['type'];

        switch ($type) {
            case ClassMetadata::ONE_TO_ONE:
                $this->handleOneToOneRelation($entity, $relationName, $relationData, $targetEntity, $options);
                break;
            case ClassMetadata::ONE_TO_MANY:
                $this->handleOneToManyRelation($entity, $relationName, $relationData, $targetEntity, $options);
                break;
            case ClassMetadata::MANY_TO_ONE:
                $this->handleManyToOneRelation($entity, $relationName, $relationData, $targetEntity, $options);
                break;
            case ClassMetadata::MANY_TO_MANY:
                $this->handleManyToManyRelation($entity, $relationName, $relationData, $targetEntity, $options);
                break;
        }
    }

    private function handleOneToOneRelation(object $entity, string $relationName, mixed $relationData, string $targetEntity, array $options): void 
    {
        if (is_array($relationData)) {
            $relatedEntity = new $targetEntity();
            foreach ($relationData as $key => $value) {
                $this->propertyAccessor->setValue($relatedEntity, $key, $value);
            }
            $this->entityManager->persist($relatedEntity);
        } else {
            $relatedEntity = $this->entityManager->getRepository($targetEntity)->find($relationData);
        }

        if ($relatedEntity) {
            $this->propertyAccessor->setValue($entity, $relationName, $relatedEntity);
        }
    }

    private function handleOneToManyRelation(object $entity, string $relationName, mixed $relationData, string $targetEntity, array $options): void 
    {
        if (!is_array($relationData)) {
            return;
        }

        $collection = $this->propertyAccessor->getValue($entity, $relationName);
        foreach ($relationData as $item) {
            $relatedEntity = new $targetEntity();
            foreach ($item as $key => $value) {
                $this->propertyAccessor->setValue($relatedEntity, $key, $value);
            }
            $collection->add($relatedEntity);
            $this->entityManager->persist($relatedEntity);
        }
    }

    private function handleManyToOneRelation(object $entity, string $relationName, mixed $relationData, string $targetEntity, array $options): void 
    {
        // Si c'est une relation avec le client et qu'on a un utilisateur dans les options
        if ($relationName === 'client' && isset($options['user'])) {
            $this->propertyAccessor->setValue($entity, $relationName, $options['user']);
            return;
        }

        if (is_array($relationData)) {
            $relatedEntity = new $targetEntity();
            foreach ($relationData as $key => $value) {
                $this->propertyAccessor->setValue($relatedEntity, $key, $value);
            }
            $this->entityManager->persist($relatedEntity);
        } else {
            $relatedEntity = $this->entityManager->getRepository($targetEntity)->find($relationData);
        }

        if ($relatedEntity) {
            $this->propertyAccessor->setValue($entity, $relationName, $relatedEntity);
        }
    }

    private function handleManyToManyRelation(object $entity, string $relationName, mixed $relationData, string $targetEntity, array $options): void 
    {
        if (!is_array($relationData)) {
            return;
        }

        $collection = $this->propertyAccessor->getValue($entity, $relationName);
        foreach ($relationData as $item) {
            if (is_array($item)) {
                $relatedEntity = new $targetEntity();
                foreach ($item as $key => $value) {
                    $this->propertyAccessor->setValue($relatedEntity, $key, $value);
                }
                $this->entityManager->persist($relatedEntity);
            } else {
                $relatedEntity = $this->entityManager->getRepository($targetEntity)->find($item);
            }

            if ($relatedEntity) {
                $collection->add($relatedEntity);
            }
        }
    }
} 