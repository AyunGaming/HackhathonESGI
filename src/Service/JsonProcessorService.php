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

class JsonProcessorService
{
    private $httpClient;
    private $entityManager;
    private $propertyAccessor;
    private $cache;
    private $logger;

    public function __construct(
        HttpClientInterface $httpClient, 
        EntityManagerInterface $entityManager,
        CacheInterface $cache,
        LoggerInterface $logger
    ) 
    {
        $this->httpClient = $httpClient;
        $this->entityManager = $entityManager;
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->cache = $cache;
        $this->logger = $logger;
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
     * Traite et sauvegarde les données JSON en base de données
     *
     * @param array $data Les données JSON à traiter
     * @param string $entityClass La classe de l'entité à créer
     * @param array $fieldMapping Mapping entre les clés JSON et les propriétés de l'entité
     * @param array $options Options supplémentaires pour le traitement
     * @return array Les entités créées
     */
    public function processAndSaveData(array $data, string $entityClass, array $fieldMapping, array $options = []): array
    {
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
            // Récupération des métadonnées avec cache
            $metadata = $this->getCachedMetadata($entityClass);
            $relations = $metadata->getAssociationMappings();

            $this->logger->info('Début du traitement de {count} éléments', [
                'count' => count($data),
                'entity' => $entityClass
            ]);

            foreach ($data as $index => $item) {
                try {
                    $entity = new $entityClass();
                    
                    // Traitement des champs simples
                    foreach ($fieldMapping as $jsonKey => $entityProperty) {
                        if (isset($item[$jsonKey]) && !isset($relations[$entityProperty])) {
                            $this->propertyAccessor->setValue($entity, $entityProperty, $item[$jsonKey]);
                        }
                    }

                    // Traitement des relations
                    foreach ($relations as $relationName => $relationMapping) {
                        if (isset($fieldMapping[$relationName])) {
                            $this->handleRelation(
                                $entity,
                                $relationName,
                                $item[$fieldMapping[$relationName]] ?? null,
                                $relationMapping,
                                $options
                            );
                        }
                    }

                    $this->entityManager->persist($entity);
                    $entities[] = $entity;

                    $count++;
                    if ($count % $batchSize === 0) {
                        $this->flushAndClear($count);
                        $this->logger->info('Batch {batch} traité', [
                            'batch' => $count / $batchSize
                        ]);
                    }
                } catch (\Exception $e) {
                    $this->logger->error('Erreur lors du traitement de l\'élément {index}: {error}', [
                        'index' => $index,
                        'error' => $e->getMessage()
                    ]);
                    throw $e;
                }
            }

            // Flush les entités restantes
            if ($count % $batchSize !== 0) {
                $this->flushAndClear($count);
            }

            $this->logger->info('Traitement terminé: {count} entités créées', [
                'count' => count($entities)
            ]);

            return $entities;
        } catch (\Exception $e) {
            $this->entityManager->rollback();
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