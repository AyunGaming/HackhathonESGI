<?php

namespace App\Tests\Service;

use App\Service\JsonProcessorService;
use App\Entity\Client;
use App\Entity\Vehicule;
use App\Entity\User;
use App\Entity\Appointement;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class JsonProcessorServiceTest extends TestCase
{
    private $httpClient;
    private $entityManager;
    private $cache;
    private $logger;
    private $service;
    private $metadata;
    private $response;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->metadata = $this->createMock(ClassMetadata::class);
        $this->response = $this->createMock(ResponseInterface::class);
        
        $this->service = new JsonProcessorService(
            $this->httpClient,
            $this->entityManager,
            $this->cache,
            $this->logger
        );
    }

    public function testProcessJsonFromUrlSuccess(): void
    {
        $url = 'https://example.com/data.json';
        $jsonData = [
            [
                'civil_title' => 'Mr',
                'last_name' => 'Doe',
                'first_name' => 'John',
                'address' => '123 Main St',
                'zip_code' => '12345',
                'phone' => '0123456789'
            ]
        ];

        $this->logger->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Début du traitement JSON depuis l\'URL: {url}', ['url' => $url]],
                ['JSON traité avec succès: {count} éléments', ['count' => count($jsonData)]]
            );

        $this->response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_OK);

        $this->response->expects($this->once())
            ->method('getContent')
            ->willReturn(json_encode($jsonData));

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', $url)
            ->willReturn($this->response);

        $result = $this->service->processJsonFromUrl($url);
        $this->assertEquals($jsonData, $result);
    }

    public function testProcessJsonFromUrlInvalidJson(): void
    {
        $url = 'https://example.com/data.json';
        $invalidJson = '{invalid json}';

        $this->logger->expects($this->exactly(1))
            ->method('info')
            ->withConsecutive(
                ['Début du traitement JSON depuis l\'URL: {url}', ['url' => $url]]
            );

        $this->logger->expects($this->exactly(2))
            ->method('error')
            ->withConsecutive(
                ['JSON invalide: {error}', $this->callback(function ($context) {
                    return isset($context['error']) && !empty($context['error']);
                })],
                ['Erreur lors du traitement du JSON: {error}', $this->callback(function ($context) {
                    return isset($context['error']) && strpos($context['error'], 'Le contenu n\'est pas un JSON valide') !== false;
                })]
            );

        $this->response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_OK);

        $this->response->expects($this->once())
            ->method('getContent')
            ->willReturn($invalidJson);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', $url)
            ->willReturn($this->response);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessageMatches('/Le contenu n\'est pas un JSON valide: .+/');

        $this->service->processJsonFromUrl($url);
    }

    public function testProcessJsonFromUrlHttpError(): void
    {
        $url = 'https://example.com/data.json';

        $this->logger->expects($this->exactly(1))
            ->method('info')
            ->withConsecutive(
                ['Début du traitement JSON depuis l\'URL: {url}', ['url' => $url]]
            );

        $this->logger->expects($this->exactly(2))
            ->method('error')
            ->withConsecutive(
                ['Erreur HTTP lors de la récupération du JSON: {status}', ['status' => Response::HTTP_NOT_FOUND]],
                ['Erreur lors du traitement du JSON: {error}', $this->callback(function ($context) {
                    return isset($context['error']) && strpos($context['error'], 'Erreur lors de la récupération du fichier JSON') !== false;
                })]
            );

        $this->response->expects($this->exactly(3))
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_NOT_FOUND);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', $url)
            ->willReturn($this->response);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Erreur lors de la récupération du fichier JSON');

        $this->service->processJsonFromUrl($url);
    }

    public function testProcessJsonFromUrlNetworkError(): void
    {
        $url = 'https://example.com/data.json';

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Début du traitement JSON depuis l\'URL: {url}', ['url' => $url]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException(new \Exception('Network error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Erreur lors du traitement du JSON: {error}', ['error' => 'Network error']);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Erreur lors du traitement du JSON: Network error');

        $this->service->processJsonFromUrl($url);
    }

    public function testProcessAndSaveDataWithClientAndVehicule(): void
    {
        // Configuration des métadonnées pour les relations
        $this->metadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn([
                'vehicules' => [
                    'type' => ClassMetadata::ONE_TO_MANY,
                    'targetEntity' => Vehicule::class
                ],
                'user' => [
                    'type' => ClassMetadata::ONE_TO_ONE,
                    'targetEntity' => User::class
                ],
                'appointements' => [
                    'type' => ClassMetadata::ONE_TO_MANY,
                    'targetEntity' => Appointement::class
                ]
            ]);

        $this->cache->expects($this->once())
            ->method('get')
            ->willReturn($this->metadata);

        $this->logger->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Début du traitement de {count} éléments', [
                    'count' => 1,
                    'entity' => Client::class
                ]],
                ['Traitement terminé: {count} entités créées', ['count' => 1]]
            );

        // Données de test avec la structure exacte des entités
        $data = [
            [
                'civil_title' => 'Mr',
                'last_name' => 'Doe',
                'first_name' => 'John',
                'address' => '123 Main St',
                'zip_code' => '75001',
                'phone' => '0123456789',
                'vehicules' => [
                    [
                        'brand' => 'Renault',
                        'model' => 'Clio',
                        'registration' => 'AB-123-CD',
                        'vin' => 'WVWZZZ1KZAW123456',
                        'circulation_date' => new \DateTime('2020-01-01'),
                        'mileage' => 50000,
                        'driver' => true,
                        'driver_last_name' => 'Doe',
                        'driver_first_name' => 'John',
                        'driver_phone' => '0123456789'
                    ]
                ],
                'user' => [
                    'email' => 'john.doe@example.com',
                    'password' => 'hashed_password'
                ]
            ]
        ];

        $fieldMapping = [
            'civil_title' => 'civilTitle',
            'last_name' => 'lastName',
            'first_name' => 'firstName',
            'address' => 'address',
            'zip_code' => 'zipCode',
            'phone' => 'phone',
            'vehicules' => 'vehicules',
            'user' => 'user'
        ];

        // Configuration des attentes pour l'EntityManager
        $this->entityManager->expects($this->exactly(3))
            ->method('persist')
            ->with($this->callback(function ($entity) {
                return $entity instanceof Client || 
                       $entity instanceof Vehicule || 
                       $entity instanceof User;
            }));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->entityManager->expects($this->once())
            ->method('clear');

        $result = $this->service->processAndSaveData($data, Client::class, $fieldMapping);
        
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Client::class, $result[0]);
    }

    public function testProcessAndSaveDataWithAppointement(): void
    {
        $this->metadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn([
                'client' => [
                    'type' => ClassMetadata::MANY_TO_ONE,
                    'targetEntity' => Client::class
                ],
                'vehicule' => [
                    'type' => ClassMetadata::MANY_TO_ONE,
                    'targetEntity' => Vehicule::class
                ]
            ]);

        $this->cache->expects($this->once())
            ->method('get')
            ->willReturn($this->metadata);

        $this->logger->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Début du traitement de {count} éléments', [
                    'count' => 1,
                    'entity' => Appointement::class
                ]],
                ['Traitement terminé: {count} entités créées', ['count' => 1]]
            );

        // Mock des repositories pour les entités existantes
        $existingClient = new Client();
        $existingVehicule = new Vehicule();
        
        $clientRepository = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $clientRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($existingClient);

        $vehiculeRepository = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $vehiculeRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($existingVehicule);

        $this->entityManager->expects($this->exactly(2))
            ->method('getRepository')
            ->willReturnCallback(function ($class) use ($clientRepository, $vehiculeRepository) {
                return $class === Client::class ? $clientRepository : $vehiculeRepository;
            });

        $data = [
            [
                'date' => new \DateTime('2024-03-20 14:30:00'),
                'client' => 1,
                'vehicule' => 1
            ]
        ];

        $fieldMapping = [
            'date' => 'date',
            'client' => 'client',
            'vehicule' => 'vehicule'
        ];

        $this->entityManager->expects($this->once())
            ->method('persist');

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->entityManager->expects($this->once())
            ->method('clear');

        $result = $this->service->processAndSaveData($data, Appointement::class, $fieldMapping);
        
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Appointement::class, $result[0]);
    }

    public function testProcessAndSaveDataWithError(): void
    {
        $this->metadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn([]);

        $this->cache->expects($this->once())
            ->method('get')
            ->willReturn($this->metadata);

        $this->logger->expects($this->exactly(1))
            ->method('info')
            ->withConsecutive(
                ['Début du traitement de {count} éléments', [
                    'count' => 1,
                    'entity' => Client::class
                ]]
            );

        $this->logger->expects($this->exactly(2))
            ->method('error')
            ->withConsecutive(
                ['Erreur lors du traitement de l\'élément {index}: {error}', $this->callback(function ($context) {
                    return isset($context['index']) && $context['index'] === 0 
                        && isset($context['error']) && $context['error'] === 'Database error';
                })],
                ['Erreur lors de la sauvegarde des données: {error}', $this->callback(function ($context) {
                    return isset($context['error']) && $context['error'] === 'Database error';
                })]
            );

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->willThrowException(new \Exception('Database error'));

        $this->entityManager->expects($this->once())
            ->method('rollback');

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Erreur lors de la sauvegarde des données: Database error');
        
        $this->service->processAndSaveData(
            [
                [
                    'civil_title' => 'Mr',
                    'last_name' => 'Doe',
                    'first_name' => 'John',
                    'address' => '123 Main St',
                    'zip_code' => '75001',
                    'phone' => '0123456789'
                ]
            ],
            Client::class,
            [
                'civil_title' => 'civilTitle',
                'last_name' => 'lastName',
                'first_name' => 'firstName',
                'address' => 'address',
                'zip_code' => 'zipCode',
                'phone' => 'phone'
            ]
        );
    }

    public function testProcessAndSaveDataWithInvalidEntityClass(): void
    {
        $this->logger->expects($this->once())
            ->method('error')
            ->with('Classe invalide: {class}', ['class' => 'InvalidClass']);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('La classe "InvalidClass" n\'existe pas');
        
        $this->service->processAndSaveData(
            [['test' => 'data']],
            'InvalidClass',
            ['test' => 'test']
        );
    }
} 