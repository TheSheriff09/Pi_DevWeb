<?php

namespace App\Tests\Service;

use App\Entity\Reclamations;
use App\Service\ReclamationManager;
use PHPUnit\Framework\TestCase;

/**
 * ReclamationManagerTest — Unit tests for the ReclamationManager business service.
 *
 * Business rules tested:
 *  1. A reclamation title is mandatory and cannot be empty.
 *  2. A description is mandatory and cannot be empty.
 *  3. The status must be one of the allowed values.
 *  4. A reclamation must have a valid requester ID (requestedId > 0).
 */
class ReclamationManagerTest extends TestCase
{
    // =========================================================
    // Rule 1 — Title is mandatory
    // =========================================================

    public function testValidReclamationPassesValidation(): void
    {
        $reclamation = new Reclamations();
        $reclamation->setTitle('Service delivery issue');
        $reclamation->setDescription('The service was not delivered on time.');
        $reclamation->setStatus('pending');
        $reclamation->setRequestedId(10);

        $manager = new ReclamationManager();

        $this->assertTrue($manager->validate($reclamation));
    }

    public function testReclamationWithEmptyTitleThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The reclamation title is mandatory and cannot be empty.');

        $reclamation = new Reclamations();
        $reclamation->setTitle('');
        $reclamation->setDescription('A valid description.');
        $reclamation->setStatus('pending');
        $reclamation->setRequestedId(10);

        $manager = new ReclamationManager();
        $manager->validate($reclamation);
    }

    public function testReclamationWithWhitespaceTitleThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $reclamation = new Reclamations();
        $reclamation->setTitle('   ');
        $reclamation->setDescription('A valid description.');
        $reclamation->setStatus('pending');
        $reclamation->setRequestedId(10);

        $manager = new ReclamationManager();
        $manager->validate($reclamation);
    }

    // =========================================================
    // Rule 2 — Description is mandatory
    // =========================================================

    public function testReclamationWithEmptyDescriptionThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The reclamation description is mandatory and cannot be empty.');

        $reclamation = new Reclamations();
        $reclamation->setTitle('A valid title');
        $reclamation->setDescription('');
        $reclamation->setStatus('pending');
        $reclamation->setRequestedId(10);

        $manager = new ReclamationManager();
        $manager->validate($reclamation);
    }

    // =========================================================
    // Rule 3 — Status must be a valid value
    // =========================================================

    public function testReclamationWithInvalidStatusThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $reclamation = new Reclamations();
        $reclamation->setTitle('A valid title');
        $reclamation->setDescription('A valid description.');
        $reclamation->setStatus('unknown_status');
        $reclamation->setRequestedId(10);

        $manager = new ReclamationManager();
        $manager->validate($reclamation);
    }

    public function testReclamationWithStatusResolvedPassesValidation(): void
    {
        $reclamation = new Reclamations();
        $reclamation->setTitle('Internet outage');
        $reclamation->setDescription('The internet was down for 3 hours.');
        $reclamation->setStatus('resolved');
        $reclamation->setRequestedId(7);

        $manager = new ReclamationManager();

        $this->assertTrue($manager->validate($reclamation));
    }

    // =========================================================
    // Rule 4 — Requester ID must be valid and positive
    // =========================================================

    public function testReclamationWithNullRequesterIdThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A reclamation must have a valid requester ID (requestedId must be greater than 0).');

        $reclamation = new Reclamations();
        $reclamation->setTitle('A valid title');
        $reclamation->setDescription('A valid description.');
        $reclamation->setStatus('pending');
        $reclamation->setRequestedId(null);

        $manager = new ReclamationManager();
        $manager->validate($reclamation);
    }

    public function testReclamationWithZeroRequesterIdThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $reclamation = new Reclamations();
        $reclamation->setTitle('A valid title');
        $reclamation->setDescription('A valid description.');
        $reclamation->setStatus('pending');
        $reclamation->setRequestedId(0);

        $manager = new ReclamationManager();
        $manager->validate($reclamation);
    }

    public function testReclamationWithValidRequesterIdPassesValidation(): void
    {
        $reclamation = new Reclamations();
        $reclamation->setTitle('Billing issue');
        $reclamation->setDescription('I was charged twice for the same service.');
        $reclamation->setStatus('in_progress');
        $reclamation->setRequestedId(25);

        $manager = new ReclamationManager();

        $this->assertTrue($manager->validate($reclamation));
    }
}
