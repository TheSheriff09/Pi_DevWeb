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
    #[ORM\Column(name: '`scheduleID`', type: Types::INTEGER)]
    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    private ?int $scheduleID = null;

    #[ORM\Column(name: '`mentorID`', type: Types::INTEGER)]
    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    private ?int $mentorID = null;

    #[ORM\Column(name: '`availableDate`', type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: 'Available Date cannot be empty.')]
    private ?\DateTimeInterface $availableDate = null;

    #[ORM\Column(name: '`startTime`', type: Types::TIME_MUTABLE)]
    #[Assert\NotBlank(message: 'Start Time cannot be empty.')]
    private ?\DateTimeInterface $startTime = null;

    #[ORM\Column(name: '`endTime`', type: Types::TIME_MUTABLE)]
    #[Assert\NotBlank(message: 'End Time cannot be empty.')]
    private ?\DateTimeInterface $endTime = null;

    #[ORM\Column(name: '`isBooked`', type: Types::BOOLEAN)]
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

    public function getMentorID(): ?int
    {
        return $this->mentorID;
    }

    public function setMentorID(?int $mentorID): static
    {
        $this->mentorID = $mentorID;
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
