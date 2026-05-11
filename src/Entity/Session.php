<?php

namespace App\Entity;

use App\Repository\SessionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: '`session`')]
#[ORM\Index(columns: ['mentorID'], name: 'idx_session_mentor')]
#[ORM\Index(columns: ['entrepreneurID'], name: 'idx_session_entrepreneur')]
#[ORM\Index(columns: ['startupID'], name: 'idx_session_startup')]
class Session
{
    #[ORM\Id]
    #[ORM\Column(name: 'sessionID', type: Types::INTEGER)]
    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    private ?int $sessionID = null;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(name: 'mentorID', referencedColumnName: 'id', nullable: false)]
    private ?Users $mentor = null;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(name: 'entrepreneurID', referencedColumnName: 'id', nullable: false)]
    private ?Users $entrepreneur = null;

    #[ORM\ManyToOne(targetEntity: Startup::class)]
    #[ORM\JoinColumn(name: 'startupID', referencedColumnName: 'startup_id', nullable: false)]
    private ?Startup $startup = null;

    #[ORM\Column(name: 'scheduleID', type: Types::INTEGER, nullable: true)]
    #[Assert\Type('integer')]
    private ?int $scheduleID = null;

    #[ORM\Column(name: 'sessionDate', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $sessionDate = null;

    #[ORM\Column(name: 'sessionType', type: Types::STRING, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Assert\Type('string')]
    private ?string $sessionType = null;

    #[ORM\Column(name: '`status`', type: Types::STRING)]
    #[Assert\Length(max: 255)]
    #[Assert\Type('string')]
    private ?string $status = null;

    #[ORM\Column(name: '`notes`', type: Types::TEXT, nullable: true)]
    #[Assert\Type('string')]
    private ?string $notes = null;

    #[ORM\Column(name: 'successProbability', type: Types::FLOAT, nullable: true)]
    #[Assert\Type('float')]
    private ?float $successProbability = null;

    #[ORM\Column(name: 'meetingLink', type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Type('string')]
    private ?string $meetingLink = null;

    public function getSessionID(): ?int
    {
        return $this->sessionID;
    }

    public function setSessionID(?int $sessionID): static
    {
        $this->sessionID = $sessionID;
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

    public function getEntrepreneur(): ?Users
    {
        return $this->entrepreneur;
    }

    public function setEntrepreneur(?Users $entrepreneur): static
    {
        $this->entrepreneur = $entrepreneur;
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

    public function getMeetingLink(): ?string
    {
        return $this->meetingLink;
    }

    public function setMeetingLink(?string $meetingLink): static
    {
        $this->meetingLink = $meetingLink;
        return $this;
    }

}
