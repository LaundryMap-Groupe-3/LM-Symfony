<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\PasswordResetToken;
use App\Repository\PasswordResetTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class PasswordResetService
{
    private const TOKEN_EXPIRY_HOURS = 24;
    private const TOKEN_LENGTH = 32;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private PasswordResetTokenRepository $passwordResetTokenRepository,
        private EmailService $emailService,
        private LoggerInterface $logger,
        private string $frontendUrl = 'http://localhost:3000'
    ) {}

    /**
     * Generate a password reset token for a user
     */
    public function generateResetToken(User $user): string
    {
        // Invalidate any existing tokens
        $existingTokens = $this->passwordResetTokenRepository->findBy(['user' => $user, 'isUsed' => false]);
        foreach ($existingTokens as $token) {
            $token->setIsUsed(true);
            $token->setUsedAt(new \DateTime());
        }

        // Create new token
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));

        $resetToken = new PasswordResetToken();
        $resetToken->setUser($user);
        $resetToken->setToken($token);
        $resetToken->setCreatedAt(new \DateTime());
        $resetToken->setExpiresAt(new \DateTime(sprintf('+%d hours', self::TOKEN_EXPIRY_HOURS)));

        $this->entityManager->persist($resetToken);
        $this->entityManager->flush();

        return $token;
    }

    /**
     * Validate a password reset token
     */
    public function validateToken(string $token): PasswordResetToken|null
    {
        $resetToken = $this->passwordResetTokenRepository->findOneBy(['token' => $token]);

        if (!$resetToken || !$resetToken->isValid()) {
            return null;
        }

        return $resetToken;
    }

    /**
     * Reset password using a valid token
     */
    public function resetPassword(string $token, string $newPassword, $passwordHasher): bool
    {
        $resetToken = $this->validateToken($token);

        if (!$resetToken) {
            $this->logger->warning('Invalid password reset token attempt', ['token' => substr($token, 0, 10)]);
            return false;
        }

        try {
            $user = $resetToken->getUser();
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);

            $resetToken->setIsUsed(true);
            $resetToken->setUsedAt(new \DateTime());

            $this->entityManager->flush();

            $this->logger->info('Password reset successfully', ['userId' => $user->getId()]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Error resetting password', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Send password reset email
     */
    public function sendResetEmail(User $user, string $resetToken): bool
    {
        $resetUrl = sprintf(
            '%s/reset-password?token=%s',
            rtrim($this->frontendUrl, '/'),
            $resetToken
        );

        return $this->emailService->sendPasswordResetEmail(
            $user->getEmail(),
            $user->getFirstName() ?? 'User',
            $resetUrl
        );
    }

    /**
     * Handle forgot password request
     */
    public function handleForgotPasswordRequest(User $user): bool
    {
        try {
            $token = $this->generateResetToken($user);
            $this->sendResetEmail($user, $token);
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Error handling forgot password request', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Clean up expired tokens
     */
    public function cleanupExpiredTokens(): int
    {
        $thirtyDaysAgo = new \DateTime('-30 days');
        
        $query = $this->entityManager
            ->createQuery(
                'DELETE FROM App\Entity\PasswordResetToken t 
                 WHERE t.createdAt < :date OR (t.isUsed = true AND t.usedAt < :date)'
            )
            ->setParameter('date', $thirtyDaysAgo);

        return $query->execute();
    }
}
