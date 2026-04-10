<?php

namespace App\Security;

final class PasswordPolicy
{
    public const MIN_LENGTH = 12;

    public static function getValidationError(?string $password): ?string
    {
        if ($password === null || $password === '') {
            return 'validation.password_required';
        }

        if (strlen($password) < self::MIN_LENGTH) {
            return 'validation.password_too_short';
        }

        $hasLower = preg_match('/[a-z]/', $password) === 1;
        $hasUpper = preg_match('/[A-Z]/', $password) === 1;
        $hasDigit = preg_match('/\d/', $password) === 1;
        $hasSpecial = preg_match('/[^a-zA-Z\d]/', $password) === 1;

        if (!$hasLower || !$hasUpper || !$hasDigit || !$hasSpecial) {
            return 'validation.password_weak';
        }

        return null;
    }
}
