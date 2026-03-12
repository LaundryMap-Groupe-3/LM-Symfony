<?php

namespace App\Controller;

use App\Entity\Admin;
use App\Entity\User;
use App\Enum\UserStatusEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class AuthController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            return $this->json(['error' => 'Email and password are required'], 400);
        }

        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return $this->json(['error' => 'User already exists'], 409);
        }

        $existingAdmin = $entityManager->getRepository(Admin::class)->findOneBy(['email' => $data['email']]);
        if ($existingAdmin) {
            return $this->json(['error' => 'User already exists'], 409);
        }

        $user = new User();
        $user->setEmail($data['email']);
        
        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);
        
        if (isset($data['firstName'])) {
            $user->setFirstName($data['firstName']);
        }
        if (isset($data['lastName'])) {
            $user->setLastName($data['lastName']);
        }

        $user->setStatus(UserStatusEnum::PENDING);
        $user->setCreatedAt(new \DateTime());

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json([
            'message' => 'User created successfully',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
            ]
        ], 201);
    }

    #[Route('/api/login_check', name: 'api_login_check', methods: ['POST'])]
    public function loginCheck(): void
    {
        // This controller can be blank: it will be intercepted by the json_login firewall
    }

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Not authenticated'], 401);
        }

        if ($user instanceof Admin) {
            return $this->json([
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'type' => 'admin',
                'roles' => $user->getRoles(),
            ]);
        }

        if ($user instanceof User) {
            $response = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'status' => $user->getStatus()->value,
                'type' => 'user',
                'roles' => $user->getRoles(),
            ];

            if ($user->getProfessional() !== null) {
                $professional = $user->getProfessional();
                $response['type'] = 'professional';
                $response['professional'] = [
                    'id' => $professional->getId(),
                    'siren' => $professional->getSiren(),
                    'status' => $professional->getStatus()->value,
                    'validationDate' => $professional->getValidationDate()?->format('c'),
                ];
            }

            return $this->json($response);
        }

        return $this->json(['error' => 'Unknown user type'], 500);
    }
}
