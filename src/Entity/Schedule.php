<?php

namespace App\Entity;

use App\Repository\ScheduleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: '`schedule`')]
class Schedule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'scheduleID', type: Types::INTEGER)]
    #[Assert\Type('integer')]
    private ?int $scheduleID = null;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(name: 'mentorID', referencedColumnName: 'id', nullable: false)]
    private ?Users $mentor = null;

    #[ORM\Column(name: 'availableDate', type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\NotBlank(message: 'Available Date cannot be empty.')]
    private ?\DateTimeInterface $availableDate = null;

    #[ORM\Column(name: 'startTime', type: Types::TIME_MUTABLE, nullable: true)]
    #[Assert\NotBlank(message: 'Start Time cannot be empty.')]
    private ?\DateTimeInterface $startTime = null;

    #[ORM\Column(name: 'endTime', type: Types::TIME_MUTABLE, nullable: true)]
    #[Assert\NotBlank(message: 'End Time cannot be empty.')]
    private ?\DateTimeInterface $endTime = null;

    #[ORM\Column(name: 'isBooked', type: Types::BOOLEAN)]
    private ?bool $isBooked = null;

    public function getScheduleID(): ?int
    {
        return $this->scheduleID;
    }

    public function setScheduleID(?int $scheduleID): static
    {
        $this->scheduleID = $scheduleID;
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

    public function getAvailableDate(): ?\DateTimeInterface
    {
        return $this->availableDate;
    }

    public function setAvailableDate(?\DateTimeInterface $availableDate): static
    {
        $this->availableDate = $availableDate;
        return $this;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(?\DateTimeInterface $startTime): static
    {
        $this->startTime = $startTime;
        return $this;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(?\DateTimeInterface $endTime): static
    {
        $this->endTime = $endTime;
        return $this;
    }

    public function getIsBooked(): ?bool
    {
        return $this->isBooked;
    }

    public function setIsBooked(?bool $isBooked): static
    {
        $this->isBooked = $isBooked;
        return $this;
    }

}
