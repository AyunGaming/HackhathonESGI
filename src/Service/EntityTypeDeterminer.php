<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

class EntityTypeDeterminer
{
    private const ENTITY_SIGNATURES = [
        'dealership' => ['dealership_name', 'city', 'address', 'zipcode', 'latitude', 'longitude'],
        'vehicle' => ['brand', 'model', 'year', 'price'],
        'service' => ['operation_name', 'category', 'time_unit', 'price'],
        'appointment' => ['preferred_datetime']
    ];

    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    public function determineType(array $item): ?string
    {
        // Ignorer la clé car_immatriculation car elle est traitée séparément
        if (isset($item['car_immatriculation'])) {
            unset($item['car_immatriculation']);
        }

        foreach (self::ENTITY_SIGNATURES as $type => $signatureFields) {
            $matchCount = $this->countMatchingFields($item, $signatureFields);
            
            if ($matchCount >= count($signatureFields) * 0.7) {
                $this->logTypeDetermination($type, $matchCount, $item);
                return $type;
            }
        }

        $this->logUndeterminedType($item);
        return null;
    }

    private function countMatchingFields(array $item, array $signatureFields): int
    {
        $matchCount = 0;
        foreach ($signatureFields as $field) {
            if (isset($item[$field])) {
                $matchCount++;
            }
        }
        return $matchCount;
    }

    private function logTypeDetermination(string $type, int $matchCount, array $item): void
    {
        $this->logger->debug('Type d\'entité déterminé: {type} avec {count} champs correspondants', [
            'type' => $type,
            'count' => $matchCount,
            'fields' => array_keys($item)
        ]);
    }

    private function logUndeterminedType(array $item): void
    {
        $this->logger->warning('Type d\'entité non déterminé pour: {data}', [
            'data' => $item,
            'available_fields' => array_keys($item)
        ]);
    }
} 