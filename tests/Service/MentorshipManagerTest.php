<?php

namespace App\Tests\Service;

use App\Entity\Session;
use App\Service\MentorshipManager;
use PHPUnit\Framework\TestCase;

class MentorshipManagerTest extends TestCase
{
    public function testValidMentorshipPasses(): void
    {
        $session = new Session();
        $session->setMentorID(1);
        $session->setEntrepreneurID(2);
        $session->setSessionDate(new \DateTime('tomorrow'));
        $session->setSuccessProbability(75.5);

        $manager = new MentorshipManager();
        $this->assertTrue($manager->validate($session));
    }

    public function testInvalidMentorIdThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $session = new Session();
        $session->setMentorID(0);
        
        $manager = new MentorshipManager();
        $manager->validate($session);
    }

    public function testPastDateThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $session = new Session();
        $session->setMentorID(1);
        $session->setEntrepreneurID(2);
        $session->setSessionDate(new \DateTime('yesterday'));

        $manager = new MentorshipManager();
        $manager->validate($session);
    }

    public function testNegativeProbabilityThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $session = new Session();
        $session->setMentorID(1);
        $session->setEntrepreneurID(2);
        $session->setSuccessProbability(-10.0);

        $manager = new MentorshipManager();
        $manager->validate($session);
    }

    public function testInvalidEntrepreneurIdThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $session = new Session();
        $session->setMentorID(1);
        $session->setEntrepreneurID(0);
        
        $manager = new MentorshipManager();
        $manager->validate($session);
    }

    public function testNullMentorIdThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A mentorship session must have a valid mentor ID.');
        
        $session = new Session();
        $session->setMentorID(null);
        $session->setEntrepreneurID(1);
        
        $manager = new MentorshipManager();
        $manager->validate($session);
    }

    public function testNullEntrepreneurIdThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A mentorship session must have a valid entrepreneur ID.');
        
        $session = new Session();
        $session->setMentorID(1);
        $session->setEntrepreneurID(null);
        
        $manager = new MentorshipManager();
        $manager->validate($session);
    }

    public function testNegativeMentorIdThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $session = new Session();
        $session->setMentorID(-5);
        $manager = new MentorshipManager();
        $manager->validate($session);
    }

    public function testNegativeEntrepreneurIdThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $session = new Session();
        $session->setMentorID(1);
        $session->setEntrepreneurID(-1);
        $manager = new MentorshipManager();
        $manager->validate($session);
    }

    public function testTodayDatePasses(): void
    {
        $session = new Session();
        $session->setMentorID(1);
        $session->setEntrepreneurID(1);
        $session->setSessionDate(new \DateTime('today'));
        $manager = new MentorshipManager();
        $this->assertTrue($manager->validate($session));
    }

    public function testMaximumProbabilityPasses(): void
    {
        $session = new Session();
        $session->setMentorID(1);
        $session->setEntrepreneurID(1);
        $session->setSuccessProbability(100.0);
        $manager = new MentorshipManager();
        $this->assertTrue($manager->validate($session));
    }

    public function testFutureDateFarAheadPasses(): void
    {
        $session = new Session();
        $session->setMentorID(1);
        $session->setEntrepreneurID(1);
        $session->setSessionDate(new \DateTime('+1 year'));
        $manager = new MentorshipManager();
        $this->assertTrue($manager->validate($session));
    }
}
