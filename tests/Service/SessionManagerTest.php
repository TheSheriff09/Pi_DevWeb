<?php

namespace App\Tests\Service;

use App\Entity\Session;
use App\Service\SessionManager;
use PHPUnit\Framework\TestCase;

/**
 * SessionManagerTest — Unit tests for the SessionManager business service.
 *
 * Business rules tested:
 *  1. A session must have a valid mentor ID (mentorID > 0).
 *  2. A session must have a valid entrepreneur ID (entrepreneurID > 0).
 *  3. The session date cannot be set in the past.
 *  4. The session type must be one of the allowed values.
 */
class SessionManagerTest extends TestCase
{
    // =========================================================
    // Helper — build a valid session
    // =========================================================

    private function makeValidSession(): Session
    {
        $session = new Session();
        $session->setMentorID(1);
        $session->setEntrepreneurID(2);
        $session->setSessionDate(new \DateTime('tomorrow'));
        $session->setSessionType('online');

        return $session;
    }

    // =========================================================
    // Rule 1 — Mentor ID must be valid and positive
    // =========================================================

    public function testValidSessionPassesValidation(): void
    {
        $manager = new SessionManager();

        $this->assertTrue($manager->validate($this->makeValidSession()));
    }

    public function testSessionWithNullMentorIdThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A session must have a valid mentor ID (mentorID must be greater than 0).');

        $session = $this->makeValidSession();
        $session->setMentorID(null);

        $manager = new SessionManager();
        $manager->validate($session);
    }

    public function testSessionWithZeroMentorIdThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $session = $this->makeValidSession();
        $session->setMentorID(0);

        $manager = new SessionManager();
        $manager->validate($session);
    }

    // =========================================================
    // Rule 2 — Entrepreneur ID must be valid and positive
    // =========================================================

    public function testSessionWithNullEntrepreneurIdThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A session must have a valid entrepreneur ID (entrepreneurID must be greater than 0).');

        $session = $this->makeValidSession();
        $session->setEntrepreneurID(null);

        $manager = new SessionManager();
        $manager->validate($session);
    }

    public function testSessionWithNegativeEntrepreneurIdThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $session = $this->makeValidSession();
        $session->setEntrepreneurID(-10);

        $manager = new SessionManager();
        $manager->validate($session);
    }

    public function testSessionWithValidEntrepreneurIdPassesValidation(): void
    {
        $session = $this->makeValidSession();
        $session->setEntrepreneurID(99);

        $manager = new SessionManager();

        $this->assertTrue($manager->validate($session));
    }

    // =========================================================
    // Rule 3 — Session date cannot be in the past
    // =========================================================

    public function testSessionWithPastDateThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The session date cannot be set in the past.');

        $session = $this->makeValidSession();
        $session->setSessionDate(new \DateTime('yesterday'));

        $manager = new SessionManager();
        $manager->validate($session);
    }

    public function testSessionWithFutureDatePassesValidation(): void
    {
        $session = $this->makeValidSession();
        $session->setSessionDate(new \DateTime('+7 days'));

        $manager = new SessionManager();

        $this->assertTrue($manager->validate($session));
    }

    public function testSessionWithTodayDatePassesValidation(): void
    {
        $session = $this->makeValidSession();
        $session->setSessionDate(new \DateTime('today'));

        $manager = new SessionManager();

        $this->assertTrue($manager->validate($session));
    }

    // =========================================================
    // Rule 4 — Session type must be one of the allowed values
    // =========================================================

    public function testSessionWithInvalidTypeThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $session = $this->makeValidSession();
        $session->setSessionType('unknown_type');

        $manager = new SessionManager();
        $manager->validate($session);
    }

    public function testSessionWithTypeInPersonPassesValidation(): void
    {
        $session = $this->makeValidSession();
        $session->setSessionType('in_person');

        $manager = new SessionManager();

        $this->assertTrue($manager->validate($session));
    }

    public function testSessionWithTypeHybridPassesValidation(): void
    {
        $session = $this->makeValidSession();
        $session->setSessionType('hybrid');

        $manager = new SessionManager();

        $this->assertTrue($manager->validate($session));
    }
}
