<?php

namespace App\Controller;

use App\Entity\Appointement;
use App\Service\ChatbotService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

#[Route('/api/chatbot')]
class ChatbotController extends AbstractController
{
    public function __construct(
        private readonly ChatbotService $chatbotService,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
    }

    #[Route('/initialize', name: 'chatbot_initialize', methods: ['GET'])]
    public function initialize(Request $request): JsonResponse
    {
        try {
            $userInfo = $request->query->all();
            if (empty($userInfo)) {
                return $this->json(['error' => 'No parameters provided'], 400);
            }

            $response = $this->chatbotService->initializeChat($userInfo);
            return $this->json($response);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/message', name: 'chatbot_message', methods: ['POST'])]
    public function sendMessage(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json(['error' => 'Invalid JSON'], 400);
            }

            if (!isset($data['message'])) {
                return $this->json(['error' => 'Missing message field'], 400);
            }

            $response = $this->chatbotService->sendMessage($data['message']);
            return $this->json($response);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/reset', name: 'chatbot_reset', methods: ['POST'])]
    public function reset(): JsonResponse
    {
        try {
            // Récupérer le dernier rendez-vous en attente pour l'utilisateur connecté
            $lastAppointment = $this->entityManager->getRepository(Appointement::class)
                ->findOneBy(
                    ['client' => $this->getUser()->getClient(), 'status' => Appointement::STATUS_PENDING],
                    ['id' => 'DESC']
                );

            if (!$lastAppointment) {
                return $this->json([
                    'error' => 'Aucun rendez-vous en attente trouvé pour cet utilisateur'
                ], 404);
            }

            // Valider le rendez-vous
            $lastAppointment->setStatus(Appointement::STATUS_VALIDATED);
            $this->entityManager->persist($lastAppointment);
            $this->entityManager->flush();

            $this->logger->info('Rendez-vous validé', [
                'appointment_id' => $lastAppointment->getId(),
                'user_id' => $this->getUser()->getId()
            ]);

            // Réinitialiser le chat
            $this->chatbotService->resetChat();

            return $this->json([
                'message' => 'Rendez-vous validé avec succès',
                'appointment_id' => $lastAppointment->getId()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la validation du rendez-vous: {error}', [
                'error' => $e->getMessage()
            ]);
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
}