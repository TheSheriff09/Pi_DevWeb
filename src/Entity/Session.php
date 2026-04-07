<?php

namespace App\Entity;

use App\Repository\SessionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: '`session`')]
class Session
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    private ?int $sessionID = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    private ?int $mentorID = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    private ?int $entrepreneurID = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\Type('integer')]
    private ?int $startupID = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\Type('integer')]
    private ?int $scheduleID = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $sessionDate = null;

    #[ORM\Column(type: Types::STRING)]
    #[Assert\Length(max: 255)]
    #[Assert\Type('string')]
    private ?string $sessionType = null;

    #[ORM\Column(type: Types::STRING)]
    #[Assert\Length(max: 255)]
    #[Assert\Type('string')]
    private ?string $status = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Type('string')]
    private ?string $notes = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Assert\Type('float')]
    private ?float $successProbability = null;

    public function getSessionID(): ?int
    {
        return $this->sessionID;
    }

    public function setSessionID(?int $sessionID): static
    {
        $this->sessionID = $sessionID;
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

    public function getEntrepreneurID(): ?int
    {
        return $this->entrepreneurID;
    }

    public function setEntrepreneurID(?int $entrepreneurID): static
    {
        $this->entrepreneurID = $entrepreneurID;
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

    public function getScheduleID(): ?int
    {
        return $this->scheduleID;
    }

    public function setScheduleID(?int $scheduleID): static
    {
        $this->scheduleID = $scheduleID;
        return $this;
    }

    public function getSessionDate(): ?\DateTimeInterface
    {
        return $this->sessionDate;
    }

    public function setSessionDate(?\DateTimeInterface $sessionDate): static
    {
        $this->sessionDate = $sessionDate;
        return $this;
    }

    public function getSessionType(): ?string
    {
        return $this->sessionType;
    }

    public function setSessionType(?string $sessionType): static
    {
        $this->sessionType = $sessionType;
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

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function getSuccessProbability(): ?float
    {
        return $this->successProbability;
    }

    public function setSuccessProbability(?float $successProbability): static
    {
        $this->successProbability = $successProbability;
        return $this;
    }

}
