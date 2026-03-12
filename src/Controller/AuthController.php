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
        $errors = [];

        // Validation des champs requis
        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email format is invalid';
        }

        if (empty($data['password'])) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($data['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters long';
        }

        // Validation des champs optionnels s'ils sont fournis
        if (isset($data['firstName']) && strlen(trim($data['firstName'])) === 0) {
            $errors['firstName'] = 'First name cannot be empty';
        }
        if (isset($data['lastName']) && strlen(trim($data['lastName'])) === 0) {
            $errors['lastName'] = 'Last name cannot be empty';
        }

        // Retourner les erreurs de validation
        if (!empty($errors)) {
            return $this->json(['errors' => $errors], 400);
        }

        // Vérifier que l'email n'existe pas
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return $this->json(['error' => 'This email is already registered'], 409);
        }

        $existingAdmin = $entityManager->getRepository(Admin::class)->findOneBy(['email' => $data['email']]);
        if ($existingAdmin) {
            return $this->json(['error' => 'This email is already registered'], 409);
        }

        // Créer le nouvel utilisateur
        $user = new User();
        $user->setEmail(strtolower(trim($data['email'])));
        
        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);
        
        if (isset($data['firstName'])) {
            $user->setFirstName(trim($data['firstName']));
        }
        if (isset($data['lastName'])) {
            $user->setLastName(trim($data['lastName']));
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
                'status' => $user->getStatus()->value,
                'type' => 'user'
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
