<?php

namespace App\Tests\Service;

use App\Entity\Fundingapplication;
use App\Service\FundingManager;
use PHPUnit\Framework\TestCase;

class FundingManagerTest extends TestCase
{
    public function testValidFundingPasses(): void
    {
        $app = new Fundingapplication();
        $app->setAmount(10000.0);
        $app->setStatus('pending');
        $app->setApplicationReason('Expansion');
        $app->setSubmissionDate(new \DateTime('today'));

        $manager = new FundingManager();
        $this->assertTrue($manager->validate($app));
    }

    public function testZeroAmountThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $app = new Fundingapplication();
        $app->setAmount(0);
        
        $manager = new FundingManager();
        $manager->validate($app);
    }

    public function testInvalidStatusThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $app = new Fundingapplication();
        $app->setAmount(1000.0);
        $app->setStatus('invalid_status');

        $manager = new FundingManager();
        $manager->validate($app);
    }

    public function testFutureDateThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $app = new Fundingapplication();
        $app->setAmount(1000.0);
        $app->setStatus('pending');
        $app->setApplicationReason('Reason');
        $app->setSubmissionDate(new \DateTime('+1 day'));

        $manager = new FundingManager();
        $manager->validate($app);
    }

    public function testNegativeAmountThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $app = new Fundingapplication();
        $app->setAmount(-500.0);
        
        $manager = new FundingManager();
        $manager->validate($app);
    }

    public function testAllValidStatusesPass(): void
    {
        $manager = new FundingManager();
        $statuses = ['pending', 'approved', 'rejected'];
        
        foreach ($statuses as $status) {
            $app = new Fundingapplication();
            $app->setAmount(1000.0);
            $app->setStatus($status);
            $app->setApplicationReason('Reason');
            $app->setSubmissionDate(new \DateTime('today'));
            $this->assertTrue($manager->validate($app), "Failed for status: $status");
        }
    }

    public function testEmptyReasonThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $app = new Fundingapplication();
        $app->setAmount(1000.0);
        $app->setStatus('pending');
        $app->setApplicationReason(' '); // Whitespace/Empty
        
        $manager = new FundingManager();
        $manager->validate($app);
    }

    public function testReasonExactlyLimitPasses(): void
    {
        $app = new Fundingapplication();
        $app->setAmount(1000.0);
        $app->setStatus('pending');
        $app->setApplicationReason(str_repeat('a', 255));
        $manager = new FundingManager();
        $this->assertTrue($manager->validate($app));
    }

    public function testCaseSensitiveStatusThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $app = new Fundingapplication();
        $app->setAmount(1000.0);
        $app->setStatus('PENDING'); // Should be lowercase 'pending'
        $manager = new FundingManager();
        $manager->validate($app);
    }

    public function testNullReasonThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $app = new Fundingapplication();
        $app->setAmount(1000.0);
        $app->setStatus('pending');
        $app->setApplicationReason(null);
        $manager = new FundingManager();
        $manager->validate($app);
    }

    public function testVeryOldSubmissionDatePasses(): void
    {
        $app = new Fundingapplication();
        $app->setAmount(1000.0);
        $app->setStatus('pending');
        $app->setApplicationReason('Old Application');
        $app->setSubmissionDate(new \DateTime('1990-01-01'));
        $manager = new FundingManager();
        $this->assertTrue($manager->validate($app));
    }
}
