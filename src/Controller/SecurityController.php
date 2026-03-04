<?php

namespace App\Controller;

use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class SecurityController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    #[OA\Post(
        path: '/api/login',
        summary: 'Connexion et obtention du token JWT',
        tags: ['Authentification'],
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'email', type: 'string', example: 'user@example.com'),
                new OA\Property(property: 'password', type: 'string', example: 'motdepasse'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Retourne le token JWT',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGci...'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Identifiants invalides')]
    public function login(): void
    {
        // Ce controller n'est jamais exécuté.
        // La route est interceptée par le firewall Lexik JWT.
    }
}