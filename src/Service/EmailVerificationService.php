<?php

namespace App\Service;

use App\Entity\EmailVerificationToken;
use App\Entity\User;
use App\Enum\UserStatusEnum;
use App\Repository\EmailVerificationTokenRepository;
use Doctrine\ORM\EntityManagerInterface;

class EmailVerificationService
{
    private const TOKEN_LENGTH = 32;
    private const EXPIRATION_HOURS = 24;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private EmailVerificationTokenRepository $tokenRepository,
    ) {
    }

    /**
     * Génère un token de vérification d'email
     */
    public function generateToken(User $user): EmailVerificationToken
    {
        // Supprimer les tokens non utilisés précédents
        $existingToken = $this->tokenRepository->findUnusedTokenForUser($user);
        if ($existingToken) {
            $this->entityManager->remove($existingToken);
            $this->entityManager->flush();
        }

        // Générer un nouveau token
        $token = new EmailVerificationToken();
        $token->setUser($user);
        $token->setToken(bin2hex(random_bytes(self::TOKEN_LENGTH)));
        $token->setCreatedAt(new \DateTime());
        $token->setExpiresAt(new \DateTime('+' . self::EXPIRATION_HOURS . ' hours'));

        $this->entityManager->persist($token);
        $this->entityManager->flush();

        return $token;
    }

    /**
     * Valide un token et marque l'email comme vérifié
     */
    public function verifyToken(string $token): ?User
    {
        $tokenEntity = $this->tokenRepository->findValidToken($token);

        if (!$tokenEntity) {
            return null;
        }

        $user = $tokenEntity->getUser();

        // Marquer le token comme utilisé
        $tokenEntity->setIsUsed(true);
        $tokenEntity->setUsedAt(new \DateTime());

        // Marquer l'email comme vérifié
        $user->setEmailVerifiedAt(new \DateTime());

        // Mettre à jour le statut à VERIFIED
        $user->setStatus(UserStatusEnum::VERIFIED);

        $this->entityManager->flush();

        return $user;
    }
}
