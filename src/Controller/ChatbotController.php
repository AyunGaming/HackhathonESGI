<?php

namespace App\Controller;

use App\Service\ChatbotService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/chatbot')]
class ChatbotController extends AbstractController
{
    public function __construct(
        private readonly ChatbotService $chatbotService
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
            $this->chatbotService->resetChat();
            return $this->json(['message' => 'Chat reset successfully']);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
} 