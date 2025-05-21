<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Response;

class EntityValidator
{
    private const IGNORED_FIELDS = ['full_name', 'address', 'phone'];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {}

    public function isValidEntityData(array $item): bool
    {
        $hasValidFields = false;
        foreach ($item as $key => $value) {
            if (!in_array($key, self::IGNORED_FIELDS)) {
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

    public function validateUser(int $userId): object
    {
        $user = $this->entityManager->getRepository('App\\Entity\\User')->find($userId);
        if (!$user) {
            $this->logger->error('Utilisateur non trouvé: {user_id}', ['user_id' => $userId]);
            throw new HttpException(Response::HTTP_NOT_FOUND, 'Utilisateur non trouvé');
        }

        $client = $user->getClient();
        if (!$client) {
            $this->logger->error('Aucun Client trouvé pour l\'utilisateur {user_id}', ['user_id' => $userId]);
            throw new HttpException(Response::HTTP_NOT_FOUND, 'Aucun Client trouvé pour cet utilisateur');
        }

        return $client;
    }

    public function validateVehicle(object $client, string $immatriculation): object
    {
        $clientVehicles = $client->getVehicules();
        foreach ($clientVehicles as $vehicle) {
            if ($vehicle->getRegistration() === $immatriculation) {
                return $vehicle;
            }
        }

        throw new HttpException(
            Response::HTTP_NOT_FOUND,
            sprintf('Aucun véhicule trouvé avec l\'immatriculation %s pour ce client', $immatriculation)
        );
    }

    public function validateExistingEntity(string $entityType, array $criteria): object
    {
        $entityClass = match($entityType) {
            'dealership' => 'App\\Entity\\Dealership',
            'service' => 'App\\Entity\\Service',
            default => throw new \InvalidArgumentException("Type d'entité non supporté: $entityType")
        };

        $entity = $this->entityManager->getRepository($entityClass)->findOneBy($criteria);
        if (!$entity) {
            throw new HttpException(
                Response::HTTP_NOT_FOUND,
                sprintf('L\'entité %s n\'existe pas dans la base de données', $entityType)
            );
        }

        return $entity;
    }
} 