<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserPreference;
use App\Entity\Language;
use App\Enum\ThemeEnum;
use App\Security\PasswordPolicy;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends AbstractController
{
    #[Route('/api/user/profile', name: 'api_user_profile_get', methods: ['GET'])]
    public function getProfile(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user || !$user instanceof User) {
            return $this->json(['error' => 'errors.not_authenticated'], 401);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'createdAt' => $user->getCreatedAt()->format('c'),
            'status' => $user->getStatus()->value,
            'emailVerifiedAt' => $user->getEmailVerifiedAt()?->format('c'),
            'lastLoginAt' => $user->getLastLoginAt()?->format('c'),
        ]);
    }

    #[Route('/api/user/profile', name: 'api_user_profile_update', methods: ['PUT'])]
    public function updateProfile(
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user || !$user instanceof User) {
            return $this->json(['error' => 'errors.not_authenticated'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $errors = [];

        // Validation
        if (isset($data['firstName'])) {
            if (empty($data['firstName'])) {
                $errors['firstName'] = 'validation.first_name_empty';
            } elseif (strlen($data['firstName']) < 2) {
                $errors['firstName'] = 'validation.first_name_required';
            } elseif (strlen(trim($data['firstName'])) > 50) {
                $errors['firstName'] = 'validation.name_max_length';
            }
        }

        if (isset($data['lastName'])) {
            if (empty($data['lastName'])) {
                $errors['lastName'] = 'validation.last_name_empty';
            } elseif (strlen($data['lastName']) < 2) {
                $errors['lastName'] = 'validation.last_name_required';
            } elseif (strlen(trim($data['lastName'])) > 50) {
                $errors['lastName'] = 'validation.name_max_length';
            }
        }

        if (!empty($errors)) {
            return $this->json(['errors' => $errors], 400);
        }

        // Update profile
        if (isset($data['firstName'])) {
            $user->setFirstName($data['firstName']);
        }

        if (isset($data['lastName'])) {
            $user->setLastName($data['lastName']);
        }

        $user->setUpdatedAt(new \DateTime());

        try {
            $entityManager->flush();
        } catch (\Exception $e) {
            return $this->json(['error' => 'errors.profile_update_error'], 500);
        }

        return $this->json([
            'message' => 'success.profile_updated',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
            ],
        ]);
    }

    #[Route('/api/user/password', name: 'api_user_password_update', methods: ['PUT'])]
    public function updatePassword(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user || !$user instanceof User) {
            return $this->json(['error' => 'errors.not_authenticated'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $errors = [];

        // Validation
        if (empty($data['currentPassword'])) {
            $errors['currentPassword'] = 'validation.password_required';
        } else {
            // Check if current password is correct
            if (!$passwordHasher->isPasswordValid($user, $data['currentPassword'])) {
                $errors['currentPassword'] = 'errors.invalid_current_password';
            }
        }

        $newPasswordError = PasswordPolicy::getValidationError($data['newPassword'] ?? null);
        if ($newPasswordError) {
            $errors['newPassword'] = $newPasswordError;
        }

        if (empty($data['confirmPassword'])) {
            $errors['confirmPassword'] = 'validation.password_confirmation_required';
        } elseif ($data['newPassword'] !== $data['confirmPassword']) {
            $errors['confirmPassword'] = 'auth.passwords_not_match';
        }

        if (!empty($errors)) {
            return $this->json(['errors' => $errors], 400);
        }

        // Cannot use same password as old password
        if ($passwordHasher->isPasswordValid($user, $data['newPassword'])) {
            return $this->json([
                'errors' => [
                    'newPassword' => 'errors.same_password_as_old'
                ]
            ], 400);
        }

        // Hash and update password
        $hashedPassword = $passwordHasher->hashPassword($user, $data['newPassword']);
        $user->setPassword($hashedPassword);
        $user->setUpdatedAt(new \DateTime());

        try {
            $entityManager->flush();
        } catch (\Exception $e) {
            return $this->json(['error' => 'errors.password_change_error'], 500);
        }

        return $this->json(['message' => 'success.password_changed']);
    }

    #[Route('/api/user/account', name: 'api_user_account_delete', methods: ['DELETE'])]
    public function deleteAccount(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user || !$user instanceof User) {
            return $this->json(['error' => 'errors.not_authenticated'], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['password'])) {
            return $this->json(['error' => 'errors.password_required'], 400);
        }

        // Verify password
        if (!$passwordHasher->isPasswordValid($user, $data['password'])) {
            return $this->json(['error' => 'errors.invalid_current_password'], 401);
        }

        try {
            // Delete user
            $entityManager->remove($user);
            $entityManager->flush();
        } catch (\Exception $e) {
            return $this->json(['error' => 'errors.account_deletion_error'], 500);
        }

        return $this->json(['message' => 'success.account_deleted']);
    }

    #[Route('/api/user/preferences', name: 'api_user_preferences_get', methods: ['GET'])]
    public function getPreferences(EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();

        if (!$user || !$user instanceof User) {
            return $this->json(['error' => 'errors.not_authenticated'], 401);
        }

        // Get or create user preferences
        $preferences = $user->getUserPreference();
        
        if (!$preferences) {
            // Create default preferences if not exists
            $preferences = new UserPreference();
            $preferences->setUser($user);
            
            // Get default language (French)
            $defaultLanguage = $entityManager->getRepository(Language::class)->findOneBy(['code' => 'fr']);
            if (!$defaultLanguage) {
                $defaultLanguage = new Language();
                $defaultLanguage->setName('Français');
                $defaultLanguage->setCode('fr');
                $entityManager->persist($defaultLanguage);
            }
            
            $preferences->setLanguage($defaultLanguage);
            $preferences->setTheme(ThemeEnum::LIGHT);
            $preferences->setNotifications(true);
            
            $user->setUserPreference($preferences);
            $entityManager->persist($preferences);
            $entityManager->flush();
        }

        return $this->json([
            'id' => $preferences->getId(),
            'language' => $preferences->getLanguage()?->getCode(),
            'languageName' => $preferences->getLanguage()?->getName(),
            'theme' => $preferences->getTheme()?->value,
            'notifications' => $preferences->isNotifications(),
        ]);
    }

    #[Route('/api/user/preferences', name: 'api_user_preferences_update', methods: ['PUT'])]
    public function updatePreferences(
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user || !$user instanceof User) {
            return $this->json(['error' => 'errors.not_authenticated'], 401);
        }

        $data = json_decode($request->getContent(), true);
        
        // Get or create preferences
        $preferences = $user->getUserPreference();
        if (!$preferences) {
            $preferences = new UserPreference();
            $preferences->setUser($user);
            $entityManager->persist($preferences);
        }

        $errors = [];

        // Update language
        if (isset($data['language'])) {
            $language = $entityManager->getRepository(Language::class)->findOneBy(['code' => $data['language']]);
            if (!$language) {
                $errors['language'] = 'validation.language_not_found';
            } else {
                $preferences->setLanguage($language);
            }
        }

        // Update theme
        if (isset($data['theme'])) {
            try {
                $theme = ThemeEnum::tryFrom($data['theme']);
                if ($theme) {
                    $preferences->setTheme($theme);
                } else {
                    $errors['theme'] = 'validation.invalid_theme';
                }
            } catch (\Exception $e) {
                $errors['theme'] = 'validation.invalid_theme';
            }
        }

        // Update notifications
        if (isset($data['notifications'])) {
            $preferences->setNotifications((bool)$data['notifications']);
        }

        if (!empty($errors)) {
            return $this->json(['errors' => $errors], 400);
        }

        try {
            $entityManager->flush();
        } catch (\Exception $e) {
            return $this->json(['error' => 'errors.preferences_update_error'], 500);
        }

        return $this->json([
            'message' => 'success.preferences_updated',
            'preferences' => [
                'id' => $preferences->getId(),
                'language' => $preferences->getLanguage()?->getCode(),
                'languageName' => $preferences->getLanguage()?->getName(),
                'theme' => $preferences->getTheme()?->value,
                'notifications' => $preferences->isNotifications(),
            ],
        ]);
    }

    #[Route('/api/languages', name: 'api_languages_list', methods: ['GET'])]
    #[IsGranted('PUBLIC_ACCESS')]
    public function getLanguages(EntityManagerInterface $entityManager): JsonResponse
    {
        $languages = $entityManager->getRepository(Language::class)->findAll();

        return $this->json([
            'languages' => array_map(function ($language) {
                return [
                    'id' => $language->getId(),
                    'code' => $language->getCode(),
                    'name' => $language->getName(),
                ];
            }, $languages),
        ]);
    }
}
