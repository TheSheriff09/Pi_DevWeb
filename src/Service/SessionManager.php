<?php

namespace App\Service;

use App\Entity\Session;

/**
 * SessionManager — Business service for the Session entity.
 *
 * Business rules enforced:
 *  1. A session must have a valid mentor ID (mentorID > 0).
 *  2. A session must have a valid entrepreneur ID (entrepreneurID > 0).
 *  3. The session date cannot be set in the past.
 *  4. The session type must be one of the allowed values.
 */
class SessionManager
{
    private const ALLOWED_TYPES = ['online', 'in_person', 'hybrid'];

    /**
     * Validates a Session object against all defined business rules.
     *
     * @throws \InvalidArgumentException when any business rule is violated.
     */
    public function validate(Session $session): bool
    {
        // Rule 1: Mentor ID is required and must be positive
        if ($session->getMentorID() === null || $session->getMentorID() <= 0) {
            throw new \InvalidArgumentException('A session must have a valid mentor ID (mentorID must be greater than 0).');
        }

        // Rule 2: Entrepreneur ID is required and must be positive
        if ($session->getEntrepreneurID() === null || $session->getEntrepreneurID() <= 0) {
            throw new \InvalidArgumentException('A session must have a valid entrepreneur ID (entrepreneurID must be greater than 0).');
        }

        // Rule 3: Session date cannot be in the past
        if ($session->getSessionDate() !== null) {
            $today = new \DateTime('today');
            if ($session->getSessionDate() < $today) {
                throw new \InvalidArgumentException('The session date cannot be set in the past.');
            }
        }

        // Rule 4: Session type must be one of the allowed values
        if (!in_array($session->getSessionType(), self::ALLOWED_TYPES, true)) {
            throw new \InvalidArgumentException(
                sprintf('The session type "%s" is not valid. Allowed types: %s.', $session->getSessionType(), implode(', ', self::ALLOWED_TYPES))
            );
        }

        return true;
    }
}
