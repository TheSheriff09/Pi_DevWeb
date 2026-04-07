<?php

namespace App\Entity;

use App\Repository\BusinessplanRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: '`businessplan`')]
class Businessplan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\Type('integer')]
    private ?int $businessPlanID = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Assert\Type('string')]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Type('string')]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Type('string')]
    private ?string $marketAnalysis = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Type('string')]
    private ?string $valueProposition = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Type('string')]
    private ?string $businessModel = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Type('string')]
    private ?string $marketingStrategy = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Type('string')]
    private ?string $financialForecast = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Assert\Type('float')]
    private ?float $fundingRequired = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Assert\Type('string')]
    private ?string $timeline = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    #[Assert\Type('string')]
    private ?string $status = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $creationDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastUpdate = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\Type('integer')]
    private ?int $startupID = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\Type('integer')]
    private ?int $userId = null;

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

    public function getFundingRequired(): ?float
    {
        return $this->fundingRequired;
    }

    public function setFundingRequired(?float $fundingRequired): static
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

    public function getStartupID(): ?int
    {
        return $this->startupID;
    }

    public function setStartupID(?int $startupID): static
    {
        $this->startupID = $startupID;
        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): static
    {
        $this->userId = $userId;
        return $this;
    }

}
