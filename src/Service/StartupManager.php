<?php

namespace App\Service;

use App\Entity\Startup;

/**
 * StartupManager — Business service for the Startup entity.
 *
 * Business rules enforced:
 *  1. Startup name is mandatory and cannot be blank.
 *  2. Funding amount cannot be negative.
 *  3. KPI score must be between 0 and 100.
 *  4. Sector must be provided.
 */
class StartupManager
{
    /**
     * Validates a Startup object against business rules.
     */
    public function validate(Startup $startup): bool
    {
        // Rule 1: Name mandatory
        if (empty(trim((string) $startup->getName()))) {
            throw new \InvalidArgumentException('The startup name is mandatory.');
        }

        // Rule 2: Funding amount cannot be negative
        if ($startup->getFundingAmount() !== null && $startup->getFundingAmount() < 0) {
            throw new \InvalidArgumentException('The funding amount cannot be negative.');
        }

        // Rule 3: KPI score must be between 0 and 100
        if ($startup->getKPIscore() !== null) {
            if ($startup->getKPIscore() < 0 || $startup->getKPIscore() > 100) {
                throw new \InvalidArgumentException('The KPI score must be between 0 and 100.');
            }
        }

        // Rule 4: Sector must be provided
        if (empty(trim((string) $startup->getSector()))) {
            throw new \InvalidArgumentException('The startup sector is mandatory.');
        }

        return true;
    }
}
