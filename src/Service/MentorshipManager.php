<?php

namespace App\Service;

use App\Entity\Session;

/**
 * MentorshipManager — Business service for the Mentorship (Session) entity.
 *
 * Business rules enforced:
 *  1. A session must have a valid mentor ID (mentorID > 0).
 *  2. A session must have a valid entrepreneur ID (entrepreneurID > 0).
 *  3. The session date cannot be in the past.
 *  4. The success probability cannot be negative.
 */
class MentorshipManager
{
    /**
     * Validates a Session object against mentorship business rules.
     */
    public function validate(Session $session): bool
    {
        // Rule 1 & 2: Mentor and Entrepreneur IDs must be positive
        if ($session->getMentorID() === null || $session->getMentorID() <= 0) {
            throw new \InvalidArgumentException('A mentorship session must have a valid mentor ID.');
        }
        if ($session->getEntrepreneurID() === null || $session->getEntrepreneurID() <= 0) {
            throw new \InvalidArgumentException('A mentorship session must have a valid entrepreneur ID.');
        }

        // Rule 3: Date cannot be in the past
        if ($session->getSessionDate() !== null) {
            $today = new \DateTime('today');
            if ($session->getSessionDate() < $today) {
                throw new \InvalidArgumentException('The mentorship session date cannot be set in the past.');
            }
        }

        // Rule 4: Success probability cannot be negative
        if ($session->getSuccessProbability() !== null && $session->getSuccessProbability() < 0) {
            throw new \InvalidArgumentException('The success probability cannot be negative.');
        }

        return true;
    }
}
