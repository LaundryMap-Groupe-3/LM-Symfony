<?php

namespace App\Controller;

use App\Entity\Admin;
use App\Entity\User;
use App\Entity\Professional;
use App\Entity\Address;
use App\Enum\UserStatusEnum;
use App\Enum\ProfessionalStatusEnum;
use App\Enum\GeolocalizationStatusEnum;
use App\Repository\EmailVerificationTokenRepository;
use App\Service\SireneService;
use App\Service\EmailVerificationService;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class AuthController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        EmailVerificationService $emailVerificationService,
        EmailService $emailService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $errors = [];

        // Validation des champs requis
        if (empty($data['email'])) {
            $errors['email'] = 'validation.email_required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'validation.email_invalid';
        }

        if (empty($data['password'])) {
            $errors['password'] = 'validation.password_required';
        } elseif (strlen($data['password']) < 8) {
            $errors['password'] = 'validation.password_too_short';
        }

        // Validation des champs optionnels s'ils sont fournis
        if (isset($data['firstName']) && strlen(trim($data['firstName'])) === 0) {
            $errors['firstName'] = 'validation.first_name_empty';
        }
        if (isset($data['lastName']) && strlen(trim($data['lastName'])) === 0) {
            $errors['lastName'] = 'validation.last_name_empty';
        }

        // Retourner les erreurs de validation
        if (!empty($errors)) {
            return $this->json(['errors' => $errors], 400);
        }

        // Vérifier que l'email n'existe pas
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return $this->json(['error' => 'validation.email_already_registered'], 409);
        }

        $existingAdmin = $entityManager->getRepository(Admin::class)->findOneBy(['email' => $data['email']]);
        if ($existingAdmin) {
            return $this->json(['error' => 'validation.email_already_registered'], 409);
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

        // Générer et envoyer le token de vérification d'email
        $verificationToken = $emailVerificationService->generateToken($user);
        $emailService->sendVerificationEmail($user, $verificationToken);

        return $this->json([
            'message' => 'User created successfully. Please check your email to verify your address.',
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
        SireneService $sireneService,
        EmailVerificationService $emailVerificationService,
        EmailService $emailService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $errors = [];

        // Validation des champs requis
        if (empty($data['email'])) {
            $errors['email'] = 'validation.email_required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'validation.email_invalid';
        }

        if (empty($data['password'])) {
            $errors['password'] = 'validation.password_required';
        } elseif (strlen($data['password']) < 8) {
            $errors['password'] = 'validation.password_too_short';
        }

        if (empty($data['firstName'])) {
            $errors['firstName'] = 'validation.first_name_required';
        }

        if (empty($data['lastName'])) {
            $errors['lastName'] = 'validation.last_name_required';
        }

        if (empty($data['siret'])) {
            $errors['siret'] = 'validation.siret_required';
        } else {
            // Vérifier le SIRET via l'API SIRENE
            $sireneResult = $sireneService->verifySiret($data['siret']);
            if (!$sireneResult['valid']) {
                $errors['siret'] = $sireneResult['error'] ?? 'validation.siret_invalid';
            }
        }

        if (empty($data['street'])) {
            $errors['street'] = 'validation.street_required';
        }

        if (empty($data['postalCode'])) {
            $errors['postalCode'] = 'validation.postal_code_required';
        } elseif (!preg_match('/^\d{5}$/', $data['postalCode'])) {
            $errors['postalCode'] = 'validation.postal_code_invalid';
        }

        if (empty($data['city'])) {
            $errors['city'] = 'validation.city_required';
        }

        if (empty($data['country'])) {
            $errors['country'] = 'validation.country_required';
        }

        if (!empty($errors)) {
            return $this->json(['errors' => $errors], 400);
        }

        // Vérifier que l'email n'existe pas
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => strtolower($data['email'])]);
        if ($existingUser) {
            return $this->json(['error' => 'validation.email_already_registered'], 409);
        }

        $existingAdmin = $entityManager->getRepository(Admin::class)->findOneBy(['email' => strtolower($data['email'])]);
        if ($existingAdmin) {
            return $this->json(['error' => 'validation.email_already_registered'], 409);
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

            // Générer et envoyer le token de vérification d'email
            $verificationToken = $emailVerificationService->generateToken($user);
            $emailService->sendVerificationEmail($user, $verificationToken);

            return $this->json([
                'message' => 'Professional account created successfully. Please check your email to verify your address.',
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
                'error' => 'errors.professional_account_error',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        JWTTokenManagerInterface $jwtManager
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
        }

        if (!empty($errors)) {
            return $this->json(['errors' => $errors], 400);
        }

        // Chercher l'utilisateur dans la table Admin
        $admin = $entityManager->getRepository(Admin::class)->findOneBy(['email' => strtolower($data['email'])]);
        if ($admin) {
            if (!$passwordHasher->isPasswordValid($admin, $data['password'])) {
                return $this->json(['error' => 'errors.invalid_email_password'], 401);
            }

            // Admin peut se connecter directement
            $token = $jwtManager->create($admin);
            return $this->json([
                'token' => $token,
                'user' => [
                    'id' => $admin->getId(),
                    'email' => $admin->getEmail(),
                    'type' => 'admin',
                ]
            ], 200);
        }

        // Chercher l'utilisateur dans la table User
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => strtolower($data['email'])]);
        if (!$user) {
            return $this->json(['error' => 'errors.invalid_email_password'], 401);
        }

        if (!$passwordHasher->isPasswordValid($user, $data['password'])) {
            return $this->json(['error' => 'errors.invalid_email_password'], 401);
        }

        // Vérifier si l'email a été vérifié
        if ($user->getEmailVerifiedAt() === null) {
            return $this->json(['error' => 'email_not_verified'], 401);
        }

        // Vérifier si le compte a été suspendu
        if ($user->getStatus() === UserStatusEnum::SUSPENDED) {
            return $this->json(['error' => 'account_suspended'], 403);
        }

        // Vérifier si c'est un professionnel et si son compte a été approuvé
        $professional = $user->getProfessional();
        if ($professional !== null) {
            if ($professional->getStatus() === ProfessionalStatusEnum::PENDING) {
                return $this->json(['error' => 'professional_pending_approval'], 403);
            }
            if ($professional->getStatus() === ProfessionalStatusEnum::REJECTED) {
                return $this->json(['error' => 'professional_rejected'], 403);
            }
        }

        // Générer le JWT token
        $token = $jwtManager->create($user);

        // Mettre à jour lastLoginAt
        $user->setLastLoginAt(new \DateTime());
        $entityManager->flush();

        return $this->json([
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'status' => $user->getStatus()->value,
                'type' => $professional !== null ? 'professional' : 'user',
                'professional' => $professional ? [
                    'id' => $professional->getId(),
                    'siret' => $professional->getSiret(),
                    'status' => $professional->getStatus()->value,
                ] : null
            ]
        ], 200);
    }

    #[Route('/api/login_check', name: 'api_login_check', methods: ['POST'])]
    public function loginCheck(): void
    {
        // This controller can be blank: it will be intercepted by the json_login firewall
    }

    #[Route('/api/verify-email', name: 'api_verify_email', methods: ['POST'])]
    public function verifyEmail(
        Request $request,
        EmailVerificationService $emailVerificationService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (empty($data['token'])) {
            return $this->json(['error' => 'errors.token_required'], 400);
        }

        $user = $emailVerificationService->verifyToken($data['token']);

        if (!$user) {
            return $this->json(['error' => 'errors.token_invalid'], 400);
        }

        return $this->json([
            'message' => 'Email verified successfully',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'status' => $user->getStatus()->value,
            ]
        ], 200);
    }

    #[Route('/api/resend-verification-email', name: 'api_resend_verification_email', methods: ['POST'])]
    public function resendVerificationEmail(
        Request $request,
        EntityManagerInterface $entityManager,
        EmailVerificationService $emailVerificationService,
        EmailService $emailService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (empty($data['email'])) {
            return $this->json(['error' => 'validation.email_required'], 400);
        }

        // Find user by email
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => strtolower($data['email'])]);

        if (!$user) {
            // Don't reveal if email exists
            return $this->json([
                'message' => 'If this email exists and is not verified, you will receive a verification email shortly.'
            ], 200);
        }

        // Check if email is already verified
        if ($user->getEmailVerifiedAt() !== null) {
            return $this->json([
                'message' => 'This email is already verified.'
            ], 200);
        }

        try {
            // Generate and send new verification token
            $verificationToken = $emailVerificationService->generateToken($user);
            $emailService->sendVerificationEmail($user, $verificationToken);

            return $this->json([
                'message' => 'Verification email has been sent successfully.'
            ], 200);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'errors.email_send_error',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'errors.not_authenticated'], 401);
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

        return $this->json(['error' => 'errors.unknown_user_type'], 500);
    }

    #[Route('/api/auth-google', name: 'app_auth_google_sso', methods: ['POST'])]
    public function authGoogle(Request $request, EntityManagerInterface $entityManager, JWTTokenManagerInterface $jwtManager) {
        try {
            $data = json_decode($request->getContent(), true);
            $googleToken = $data['token'];

            $googleUser = $this->verifyGoogleToken($googleToken);

            if (!$googleUser) {
                return $this->json(['error' => 'Token invalide'], 401);
            }

            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $googleUser['email']]);
            if($existingUser) {
                $token = $jwtManager->create($existingUser);

                // Mettre à jour lastLoginAt
                $existingUser->setLastLoginAt(new \DateTime());
                $entityManager->flush();

                $userAuthenticated = $existingUser;

            } else {
                $user = new User();

                $user->setEmail($googleUser['email'])
                    ->setLastName($googleUser['family_name'])
                    ->setFirstName($googleUser['given_name'])
                    ->setOauthId($googleUser['sub'])
                    ->setStatus(UserStatusEnum::VERIFIED)
                    ->setCreatedAt(new \DateTime());

                $entityManager->persist($user);
                $entityManager->flush();

                $token = $jwtManager->create($user);
                $userAuthenticated = $user;
            }

            return $this->json([
                'token' => $token,
                'user' => [
                    'id' => $userAuthenticated->getId(),
                    'email' => $userAuthenticated->getEmail(),
                    'firstName' => $userAuthenticated->getFirstName(),
                    'lastName' => $userAuthenticated->getLastName(),
                    'status' => $userAuthenticated->getStatus()->value,
                    'type' => 'user',
                    'professional' => null
                ]
            ], 200);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Une erreur est survenue'], 500);
        }
    }

    private function verifyGoogleToken($accessToken) {
        $url = 'https://www.googleapis.com/oauth2/v3/userinfo';
        
        $context = stream_context_create([
            'http' => [
                'header' => "Authorization: Bearer " . $accessToken,
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);

        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);

        if (empty($data['email'])) {
            return null;
        }

        return $data;
    }
}
