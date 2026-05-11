<?php

namespace App\Service;

use App\Entity\Reclamations;

/**
 * ReclamationManager — Business service for the Reclamations entity.
 *
 * Business rules enforced:
 *  1. A reclamation title is mandatory and cannot be empty.
 *  2. A description is mandatory and cannot be empty.
 *  3. The status must be one of the allowed values.
 *  4. A reclamation must have a valid requester ID (requestedId > 0).
 */
class ReclamationManager
{
    private const ALLOWED_STATUSES = ['pending', 'in_progress', 'resolved', 'rejected'];

    /**
     * Validates a Reclamations object against all defined business rules.
     *
     * @throws \InvalidArgumentException when any business rule is violated.
     */
    public function validate(Reclamations $reclamation): bool
    {
        // Rule 1: Title is mandatory
        if (empty(trim((string) $reclamation->getTitle()))) {
            throw new \InvalidArgumentException('The reclamation title is mandatory and cannot be empty.');
        }

        // Rule 2: Description is mandatory
        if (empty(trim((string) $reclamation->getDescription()))) {
            throw new \InvalidArgumentException('The reclamation description is mandatory and cannot be empty.');
        }

        // Rule 3: Status must be a valid value
        if (!in_array($reclamation->getStatus(), self::ALLOWED_STATUSES, true)) {
            throw new \InvalidArgumentException(
                sprintf('The status "%s" is not valid. Allowed statuses: %s.', $reclamation->getStatus(), implode(', ', self::ALLOWED_STATUSES))
            );
        }

        // Rule 4: A requester ID must be provided and positive
        if ($reclamation->getRequestedId() === null || $reclamation->getRequestedId() <= 0) {
            throw new \InvalidArgumentException('A reclamation must have a valid requester ID (requestedId must be greater than 0).');
        }

        return true;
    }
}
