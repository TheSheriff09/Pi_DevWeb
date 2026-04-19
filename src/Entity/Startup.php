<?php

namespace App\Entity;

use App\Repository\StartupRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: '`startup`')]
class Startup
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'startupID', type: Types::INTEGER)]
    #[Assert\Type('integer')]
    private ?int $startupID = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Assert\Type('string')]
    #[Assert\NotBlank(message: 'Startup name cannot be blank.')]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Type('string')]
    #[Assert\NotBlank(message: 'Description cannot be blank.')]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    #[Assert\Type('string')]
    #[Assert\NotBlank(message: 'Sector cannot be blank.')]
    private ?string $sector = null;

    #[ORM\Column(name: 'imageURL', type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Assert\Type('string')]
    private ?string $imageURL = null;

    #[ORM\Column(name: 'creationDate', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $creationDate = null;

    #[ORM\Column(name: 'KPIscore', type: Types::FLOAT, nullable: true)]
    #[Assert\Type('float')]
    private ?float $kPIscore = null;

    #[ORM\Column(name: 'lastEvaluationDate', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastEvaluationDate = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    #[Assert\Type('string')]
    private ?string $stage = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    #[Assert\Type('string')]
    private ?string $status = null;

    #[ORM\Column(name: 'mentorID', type: Types::INTEGER, nullable: true)]
    #[Assert\Type('integer')]
    private ?int $mentorID = null;

    #[ORM\Column(name: 'fundingAmount', type: Types::FLOAT, nullable: true)]
    #[Assert\Type('float')]
    private ?float $fundingAmount = null;

    #[ORM\Column(name: 'incubatorProgram', type: Types::STRING, length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    #[Assert\Type('string')]
    private ?string $incubatorProgram = null;

    #[ORM\Column(name: 'founderID', type: Types::INTEGER, nullable: true)]
    #[Assert\Type('integer')]
    private ?int $founderID = null;

    #[ORM\Column(name: 'businessPlanID', type: Types::INTEGER, nullable: true)]
    #[Assert\Type('integer')]
    private ?int $businessPlanID = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\Type('integer')]
    private ?int $userId = null;

    public function getStartupID(): ?int
    {
        return $this->startupID;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;
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

    public function getSector(): ?string
    {
        return $this->sector;
    }

    public function setSector(?string $sector): static
    {
        $this->sector = $sector;
        return $this;
    }

    public function getImageURL(): ?string
    {
        return $this->imageURL;
    }

    public function setImageURL(?string $imageURL): static
    {
        $this->imageURL = $imageURL;
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

    public function getKPIscore(): ?float
    {
        return $this->kPIscore;
    }

    public function setKPIscore(?float $kPIscore): static
    {
        $this->kPIscore = $kPIscore;
        return $this;
    }

    public function getLastEvaluationDate(): ?\DateTimeInterface
    {
        return $this->lastEvaluationDate;
    }

    public function setLastEvaluationDate(?\DateTimeInterface $lastEvaluationDate): static
    {
        $this->lastEvaluationDate = $lastEvaluationDate;
        return $this;
    }

    public function getStage(): ?string
    {
        return $this->stage;
    }

    public function setStage(?string $stage): static
    {
        $this->stage = $stage;
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

    public function getMentorID(): ?int
    {
        return $this->mentorID;
    }

    public function setMentorID(?int $mentorID): static
    {
        $this->mentorID = $mentorID;
        return $this;
    }

    public function getFundingAmount(): ?float
    {
        return $this->fundingAmount;
    }

    public function setFundingAmount(?float $fundingAmount): static
    {
        $this->fundingAmount = $fundingAmount;
        return $this;
    }

    public function getIncubatorProgram(): ?string
    {
        return $this->incubatorProgram;
    }

    public function setIncubatorProgram(?string $incubatorProgram): static
    {
        $this->incubatorProgram = $incubatorProgram;
        return $this;
    }

    public function getFounderID(): ?int
    {
        return $this->founderID;
    }

    public function setFounderID(?int $founderID): static
    {
        $this->founderID = $founderID;
        return $this;
    }

    public function getBusinessPlanID(): ?int
    {
        return $this->businessPlanID;
    }

    public function setBusinessPlanID(?int $businessPlanID): static
    {
        $this->businessPlanID = $businessPlanID;
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
