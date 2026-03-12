<?php

namespace App\Security;

use App\Entity\Admin;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class MultiTableUserProvider implements UserProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Load a user by their identifier (email)
     * Search in both User and Admin tables
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        // First, try to find in Admin table
        $admin = $this->entityManager->getRepository(Admin::class)->findOneBy(['email' => $identifier]);
        if ($admin) {
            return $admin;
        }

        // Then, try to find in User table
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $identifier]);
        if ($user) {
            return $user;
        }

        throw new UserNotFoundException(sprintf('User with email "%s" not found.', $identifier));
    }

    /**
     * Refresh a user
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if ($user instanceof Admin) {
            $refreshedUser = $this->entityManager->getRepository(Admin::class)->find($user->getId());
            if (!$refreshedUser) {
                throw new UserNotFoundException('Admin not found');
            }
            return $refreshedUser;
        }

        if ($user instanceof User) {
            $refreshedUser = $this->entityManager->getRepository(User::class)->find($user->getId());
            if (!$refreshedUser) {
                throw new UserNotFoundException('User not found');
            }
            return $refreshedUser;
        }

        throw new UserNotFoundException('Unsupported user class');
    }

    /**
     * Check if this provider supports the given user class
     */
    public function supportsClass(string $class): bool
    {
        return $class === User::class || $class === Admin::class || is_subclass_of($class, User::class) || is_subclass_of($class, Admin::class);
    }
}
