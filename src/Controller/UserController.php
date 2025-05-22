<?php


// src/Controller/UserController.php

// Required : Une voiture(), Plus toutes les infos obligatoires.

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    #[Route('/api/session/user', name: 'api_session_user', methods: ['GET'])]
    public function getSessionUser(Request $request): JsonResponse
    {
        $session = $request->getSession();
        $userId = $session->get('user_id');
        $userEmail = $session->get('user_email');
        $userRoles = $session->get('user_roles');
        $userClient = $session->get('user_client');

        return $this->json([
            'id' => $userId,
            'email' => $userEmail,
            'roles' => $userRoles,
            'client' => $userClient,
        ]);
    }
}