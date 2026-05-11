<?php

namespace App\Entity;

use App\Repository\StartupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: '`startup`')]
#[ORM\Index(columns: ['mentor_id'], name: 'idx_startup_mentor')]
#[ORM\Index(columns: ['user_id'], name: 'idx_startup_user')]
#[ORM\Index(columns: ['founder_id'], name: 'idx_startup_founder')]
class Startup
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'startup_id', type: Types::INTEGER)]
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

    #[ORM\Column(name: 'image_url', type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Assert\Type('string')]
    private ?string $imageURL = null;

    #[ORM\Column(name: 'creation_date', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $creationDate = null;

    #[ORM\Column(name: 'kpi_score', type: Types::FLOAT, nullable: true)]
    #[Assert\Type('float')]
    private ?float $kPIscore = null;

    #[ORM\Column(name: 'last_evaluation_date', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastEvaluationDate = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    #[Assert\Type('string')]
    private ?string $stage = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    #[Assert\Type('string')]
    private ?string $status = null;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(name: 'mentor_id', referencedColumnName: 'id', nullable: true)]
    private ?Users $mentor = null;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(name: 'founder_id', referencedColumnName: 'id', nullable: true)]
    private ?Users $founder = null;

    #[ORM\ManyToOne(targetEntity: Businessplan::class)]
    #[ORM\JoinColumn(name: 'business_plan_id', referencedColumnName: 'business_plan_id', nullable: true)]
    private ?Businessplan $businessPlan = null;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true)]
    private ?Users $user = null;

    public function __construct()
    {
        $this->creationDate = new \DateTime();
    }

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

    public function getMentor(): ?Users
    {
        return $this->mentor;
    }

    public function setMentor(?Users $mentor): static
    {
        $this->mentor = $mentor;
        return $this;
    }

    public function getFounder(): ?Users
    {
        return $this->founder;
    }

    public function setFounder(?Users $founder): static
    {
        $this->founder = $founder;
        return $this;
    }

    public function getBusinessPlan(): ?Businessplan
    {
        return $this->businessPlan;
    }

    public function setBusinessPlan(?Businessplan $businessPlan): static
    {
        $this->businessPlan = $businessPlan;
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

    // Legacy compatibility methods for Twig templates
    public function getIncubatorProgram(): ?string
    {
        return null;
    }

    public function getFundingAmount(): ?float
    {
        return null;
    }

    public function getMentorID(): ?int
    {
        return $this->mentor ? $this->mentor->getId() : null;
    }

    public function getFounderID(): ?int
    {
        return $this->founder ? $this->founder->getId() : null;
    }

    public function getBusinessPlanID(): ?int
    {
        return $this->businessPlan ? $this->businessPlan->getBusinessPlanID() : null;
    }

}
