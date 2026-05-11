<?php

namespace App\Service;

use App\Entity\Users;

/**
 * UserManager — Business service for the Users entity.
 *
 * Business rules enforced:
 *  1. The user's full name is mandatory.
 *  2. The email must be a valid email address.
 *  3. The role must be one of the allowed values.
 *  4. The password hash must be at least 8 characters long (representing raw password requirement).
 */
class UserManager
{
    private const ALLOWED_ROLES = ['ROLE_USER', 'ROLE_ADMIN', 'ROLE_MENTOR', 'ROLE_ENTREPRENEUR', 'ROLE_EVALUATOR'];

    /**
     * Validates a Users object against all defined business rules.
     *
     * @throws \InvalidArgumentException when any business rule is violated.
     */
    public function validate(Users $user): bool
    {
        // Rule 1: Full name is mandatory
        if (empty(trim((string) $user->getFullName()))) {
            throw new \InvalidArgumentException('The user\'s full name is mandatory and cannot be empty.');
        }

        // Rule 2: Email must be valid
        if (!filter_var($user->getEmail(), FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('The email address is invalid.');
        }

        // Rule 3: Role must be one of the allowed values
        if (!in_array($user->getRole(), self::ALLOWED_ROLES, true)) {
            throw new \InvalidArgumentException(
                sprintf('The role "%s" is not allowed. Allowed roles: %s.', $user->getRole(), implode(', ', self::ALLOWED_ROLES))
            );
        }

        return true;
    }

    /**
     * Validates the raw password before hashing.
     * Rule 4: Password must be at least 8 characters long.
     *
     * @throws \InvalidArgumentException when the password is too short.
     */
    public function validateRawPassword(string $rawPassword): bool
    {
        if (mb_strlen($rawPassword) < 8) {
            throw new \InvalidArgumentException('The password must be at least 8 characters long.');
        }

        return true;
    }
}
