<?php

namespace App\Controller;

use App\Entity\Admin;
use App\Entity\User;
use App\Entity\Professional;
use App\Entity\Address;
use App\Enum\UserStatusEnum;
use App\Enum\ProfessionalStatusEnum;
use App\Enum\GeolocalizationStatusEnum;
use App\Service\SireneService;
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

    #[Route('/api/register/professional', name: 'api_register_professional', methods: ['POST'])]
    public function registerProfessional(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        SireneService $sireneService
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

        if (empty($data['firstName'])) {
            $errors['firstName'] = 'First name is required';
        }

        if (empty($data['lastName'])) {
            $errors['lastName'] = 'Last name is required';
        }

        if (empty($data['siret'])) {
            $errors['siret'] = 'SIRET is required';
        } else {
            // Vérifier le SIRET via l'API SIRENE
            $sireneResult = $sireneService->verifySiret($data['siret']);
            if (!$sireneResult['valid']) {
                $errors['siret'] = $sireneResult['error'] ?? 'Invalid SIRET';
            }
        }

        if (empty($data['street'])) {
            $errors['street'] = 'Street is required';
        }

        if (empty($data['postalCode'])) {
            $errors['postalCode'] = 'Postal code is required';
        } elseif (!preg_match('/^\d{5}$/', $data['postalCode'])) {
            $errors['postalCode'] = 'Postal code must be 5 digits';
        }

        if (empty($data['city'])) {
            $errors['city'] = 'City is required';
        }

        if (empty($data['country'])) {
            $errors['country'] = 'Country is required';
        }

        if (!empty($errors)) {
            return $this->json(['errors' => $errors], 400);
        }

        // Vérifier que l'email n'existe pas
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => strtolower($data['email'])]);
        if ($existingUser) {
            return $this->json(['error' => 'This email is already registered'], 409);
        }

        $existingAdmin = $entityManager->getRepository(Admin::class)->findOneBy(['email' => strtolower($data['email'])]);
        if ($existingAdmin) {
            return $this->json(['error' => 'This email is already registered'], 409);
        }

        try {
            // Créer l'adresse
            $address = new Address();
            $address->setAddress(trim($data['street']) . ', ' . trim($data['postalCode']) . ' ' . trim($data['city']));
            $address->setStreet(trim($data['street']));
            $address->setCity(trim($data['city']));
            $address->setPostalCode((int)$data['postalCode']);
            $address->setCountry(trim($data['country']));
            $address->setGeolocalizationStatus(GeolocalizationStatusEnum::PENDING);

            // Créer l'utilisateur
            $user = new User();
            $user->setEmail(strtolower(trim($data['email'])));
            $user->setFirstName(trim($data['firstName']));
            $user->setLastName(trim($data['lastName']));
            
            $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
            $user->setStatus(UserStatusEnum::PENDING);
            $user->setCreatedAt(new \DateTime());

            // Créer le professionnel
            $professional = new Professional();
            $professional->setSiret(trim($data['siret']));
            $professional->setStatus(ProfessionalStatusEnum::PENDING);
            $professional->setUser($user);
            $professional->setAddress($address);

            // Persister tous les objets
            $entityManager->persist($address);
            $entityManager->persist($user);
            $entityManager->persist($professional);
            $entityManager->flush();

            return $this->json([
                'message' => 'Professional account created successfully',
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'status' => $user->getStatus()->value,
                    'type' => 'professional'
                ],
                'professional' => [
                    'id' => $professional->getId(),
                    'siret' => $professional->getSiret(),
                    'status' => $professional->getStatus()->value,
                ]
            ], 201);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'An error occurred while creating the professional account',
                'details' => $e->getMessage()
            ], 500);
        }
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
                    'siret' => $professional->getSiret(),
                    'status' => $professional->getStatus()->value,
                    'validationDate' => $professional->getValidationDate()?->format('c'),
                ];
            }

            return $this->json($response);
        }

        return $this->json(['error' => 'Unknown user type'], 500);
    }
}
