<?php

namespace App\Entity;

use App\Repository\BusinessplanRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: '`businessplan`')]
#[ORM\Index(columns: ['startup_id'], name: 'idx_plan_startup')]
#[ORM\Index(columns: ['user_id'], name: 'idx_plan_user')]
class Businessplan
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'business_plan_id', type: Types::INTEGER)]
    #[Assert\Type('integer')]
    private ?int $businessPlanID = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Assert\Type('string')]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Type('string')]
    private ?string $description = null;

    #[ORM\Column(name: 'market_analysis', type: Types::TEXT, nullable: true)]
    #[Assert\Type('string')]
    private ?string $marketAnalysis = null;

    #[ORM\Column(name: 'value_proposition', type: Types::TEXT, nullable: true)]
    #[Assert\Type('string')]
    private ?string $valueProposition = null;

    #[ORM\Column(name: 'business_model', type: Types::TEXT, nullable: true)]
    #[Assert\Type('string')]
    private ?string $businessModel = null;

    #[ORM\Column(name: 'marketing_strategy', type: Types::TEXT, nullable: true)]
    #[Assert\Type('string')]
    private ?string $marketingStrategy = null;

    #[ORM\Column(name: 'financial_forecast', type: Types::TEXT, nullable: true)]
    #[Assert\Type('string')]
    private ?string $financialForecast = null;

    #[ORM\Column(name: 'funding_required', type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $fundingRequired = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Assert\Type('string')]
    private ?string $timeline = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    #[Assert\Type('string')]
    private ?string $status = null;

    #[ORM\Column(name: 'creation_date', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $creationDate = null;

    #[ORM\Column(name: 'last_update', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastUpdate = null;

    #[ORM\ManyToOne(targetEntity: Startup::class)]
    #[ORM\JoinColumn(name: 'startup_id', referencedColumnName: 'startup_id', nullable: true)]
    private ?Startup $startup = null;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true)]
    private ?Users $user = null;

    public function getBusinessPlanID(): ?int
    {
        return $this->businessPlanID;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getMarketAnalysis(): ?string
    {
        return $this->marketAnalysis;
    }

    public function setMarketAnalysis(?string $marketAnalysis): static
    {
        $this->marketAnalysis = $marketAnalysis;
        return $this;
    }

    public function getValueProposition(): ?string
    {
        return $this->valueProposition;
    }

    public function setValueProposition(?string $valueProposition): static
    {
        $this->valueProposition = $valueProposition;
        return $this;
    }

    public function getBusinessModel(): ?string
    {
        return $this->businessModel;
    }

    public function setBusinessModel(?string $businessModel): static
    {
        $this->businessModel = $businessModel;
        return $this;
    }

    public function getMarketingStrategy(): ?string
    {
        return $this->marketingStrategy;
    }

    public function setMarketingStrategy(?string $marketingStrategy): static
    {
        $this->marketingStrategy = $marketingStrategy;
        return $this;
    }

    public function getFinancialForecast(): ?string
    {
        return $this->financialForecast;
    }

    public function setFinancialForecast(?string $financialForecast): static
    {
        $this->financialForecast = $financialForecast;
        return $this;
    }

    public function getFundingRequired(): ?string
    {
        return $this->fundingRequired;
    }

    public function setFundingRequired(?string $fundingRequired): static
    {
        $this->fundingRequired = $fundingRequired;
        return $this;
    }

    public function getTimeline(): ?string
    {
        return $this->timeline;
    }

    public function setTimeline(?string $timeline): static
    {
        $this->timeline = $timeline;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getCreationDate(): ?\DateTimeInterface
    {
        return $this->creationDate;
    }

    public function setCreationDate(?\DateTimeInterface $creationDate): static
    {
        $this->creationDate = $creationDate;
        return $this;
    }

    public function getLastUpdate(): ?\DateTimeInterface
    {
        return $this->lastUpdate;
    }

    public function setLastUpdate(?\DateTimeInterface $lastUpdate): static
    {
        $this->lastUpdate = $lastUpdate;
        return $this;
    }

    public function getStartup(): ?Startup
    {
        return $this->startup;
    }

    public function setStartup(?Startup $startup): static
    {
        $this->startup = $startup;
        return $this;
    }

    public function getUser(): ?Users
    {
        return $this->user;
    }

    public function setUser(?Users $user): static
    {
        $this->user = $user;
        return $this;
    }

}
