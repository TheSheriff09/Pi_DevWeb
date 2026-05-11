<?php

namespace App\Tests\Service;

use App\Entity\Startup;
use App\Service\StartupManager;
use PHPUnit\Framework\TestCase;

class StartupManagerTest extends TestCase
{
    public function testValidStartupPasses(): void
    {
        $startup = new Startup();
        $startup->setName('TechNova');
        $startup->setFundingAmount(50000.0);
        $startup->setKPIscore(85.0);
        $startup->setSector('AI');

        $manager = new StartupManager();
        $this->assertTrue($manager->validate($startup));
    }

    public function testEmptyNameThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $startup = new Startup();
        $startup->setName('');
        
        $manager = new StartupManager();
        $manager->validate($startup);
    }

    public function testNegativeFundingThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $startup = new Startup();
        $startup->setName('Valid Name');
        $startup->setFundingAmount(-100.0);

        $manager = new StartupManager();
        $manager->validate($startup);
    }

    public function testInvalidKPIScoreThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $startup = new Startup();
        $startup->setName('Valid Name');
        $startup->setKPIscore(105.0);

        $manager = new StartupManager();
        $manager->validate($startup);
    }

    public function testEmptySectorThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $startup = new Startup();
        $startup->setName('Valid Name');
        $startup->setSector('');
        
        $manager = new StartupManager();
        $manager->validate($startup);
    }

    public function testKPIBoundariesPass(): void
    {
        $manager = new StartupManager();
        
        $startup = new Startup();
        $startup->setName('A'); 
        $startup->setSector('B');
        
        $startup->setKPIscore(0.0);
        $this->assertTrue($manager->validate($startup));
        
        $startup->setKPIscore(100.0);
        $this->assertTrue($manager->validate($startup));
    }

    public function testZeroFundingPasses(): void
    {
        $startup = new Startup();
        $startup->setName('Free Start');
        $startup->setSector('Social');
        $startup->setFundingAmount(0.0);

        $manager = new StartupManager();
        $this->assertTrue($manager->validate($startup));
    }

    public function testWhitespaceNameThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $startup = new Startup();
        $startup->setName('   ');
        $manager = new StartupManager();
        $manager->validate($startup);
    }

    public function testWhitespaceSectorThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $startup = new Startup();
        $startup->setName('Valid Name');
        $startup->setSector('   ');
        $manager = new StartupManager();
        $manager->validate($startup);
    }

    public function testVeryHighFundingPasses(): void
    {
        $startup = new Startup();
        $startup->setName('Unicorn');
        $startup->setSector('Fintech');
        $startup->setFundingAmount(1000000000.0); // 1 Billion
        $manager = new StartupManager();
        $this->assertTrue($manager->validate($startup));
    }

    public function testNegativeKPIThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $startup = new Startup();
        $startup->setName('Name');
        $startup->setKPIscore(-1.0);
        $manager = new StartupManager();
        $manager->validate($startup);
    }
}
