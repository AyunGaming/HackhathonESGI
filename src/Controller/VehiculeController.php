<?php

namespace App\Controller;

use App\Entity\Vehicule;
use App\Repository\VehiculeRepository;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/vehicules')]
class VehiculeController extends AbstractController
{
    #[Route('', name: 'vehicule_index', methods: ['GET'])]
    public function index(VehiculeRepository $vehiculeRepository): JsonResponse
    {
        $vehicules = $vehiculeRepository->findAll();

        $data = [];
        foreach ($vehicules as $vehicule) {
            $data[] = $this->serializeVehicule($vehicule);
        }

        return $this->json($data);
    }

    #[Route('/user/{id}', name: 'vehicule_user', methods: ['GET'])]
    public function getAllVehicules(VehiculeRepository $vehiculeRepository, ClientRepository $clientRepository, string $id): JsonResponse
    {
        // Vérifie que l'ID est bien un nombre
        if (!ctype_digit($id)) {
            return $this->json(['error' => 'ID client invalide'], 400);
        }

        // Vérifie que le client existe
        $client = $clientRepository->find((int)$id);
        if (!$client) {
            return $this->json(['error' => 'Client non trouvé'], 404);
        }

        // Récupère les véhicules du client
        $vehicules = $vehiculeRepository->findBy(['client' => (int)$id]);

        $data = [];
        foreach ($vehicules as $vehicule) {
            $data[] = $this->serializeVehicule($vehicule);
        }

        return $this->json($data);
    }

    #[Route('/{id}', name: 'vehicule_show', methods: ['GET'])]
    public function show(Vehicule $vehicule): JsonResponse
    {
        return $this->json($this->serializeVehicule($vehicule));
    }

    #[Route('', name: 'vehicule_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, ClientRepository $clientRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $vehicule = new Vehicule();
        $vehicule->setBrand($data['brand'] ?? '')
            ->setModel($data['model'] ?? '')
            ->setRegistration($data['registration'] ?? '')
            ->setVin($data['vin'] ?? '')
            ->setCirculationDate(new \DateTime($data['circulation_date'] ?? 'now'))
            ->setMileage($data['mileage'] ?? 0)
            ->setDriver($data['driver'] ?? false)
            ->setDriverLastName($data['driver_last_name'] ?? null)
            ->setDriverFirstName($data['driver_first_name'] ?? null)
            ->setDriverPhone($data['driver_phone'] ?? null);

        // Logique Métier pour récupérer le client
        $client = $clientRepository->find($data['client_id'] ?? null);
        if (!$client) {
            return $this->json(['error' => 'Client not found'], 404);
        }
        $vehicule->setClient($client);

        $em->persist($vehicule);
        $em->flush();

        return $this->json($this->serializeVehicule($vehicule), 201);
    }

    #[Route('/{id}', name: 'vehicule_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, Vehicule $vehicule, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['brand'])) $vehicule->setBrand($data['brand']);
        if (isset($data['model'])) $vehicule->setModel($data['model']);
        if (isset($data['registration'])) $vehicule->setRegistration($data['registration']);
        if (isset($data['vin'])) $vehicule->setVin($data['vin']);
        if (isset($data['circulation_date'])) $vehicule->setCirculationDate(new \DateTime($data['circulation_date']));
        if (isset($data['mileage'])) $vehicule->setMileage($data['mileage']);
        if (isset($data['driver'])) $vehicule->setDriver($data['driver']);
        if (isset($data['driver_last_name'])) $vehicule->setDriverLastName($data['driver_last_name']);
        if (isset($data['driver_first_name'])) $vehicule->setDriverFirstName($data['driver_first_name']);
        if (isset($data['driver_phone'])) $vehicule->setDriverPhone($data['driver_phone']);

        $em->flush();

        return $this->json($this->serializeVehicule($vehicule));
    }

    #[Route('/{id}', name: 'vehicule_delete', methods: ['DELETE'])]
    public function delete(Vehicule $vehicule, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($vehicule);
        $em->flush();

        return $this->json(['message' => 'Véhicule supprimé']);
    }

    private function serializeVehicule(Vehicule $vehicule): array
    {
        return [
            'id' => $vehicule->getId(),
            'brand' => $vehicule->getBrand(),
            'model' => $vehicule->getModel(),
            'registration' => $vehicule->getRegistration(),
            'vin' => $vehicule->getVin(),
            'circulation_date' => $vehicule->getCirculationDate()?->format('Y-m-d'),
            'mileage' => $vehicule->getMileage(),
            'driver' => $vehicule->isDriver(),
            'driver_last_name' => $vehicule->getDriverLastName(),
            'driver_first_name' => $vehicule->getDriverFirstName(),
            'driver_phone' => $vehicule->getDriverPhone(),
            'client_id' => $vehicule->getClient()?->getId(),
        ];
    }
}