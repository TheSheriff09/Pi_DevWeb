<?php

namespace App\Entity;

use App\Repository\BookingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: '`booking`')]
class Booking
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    private ?int $bookingID = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    private ?int $entrepreneurID = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    private ?int $mentorID = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\Type('integer')]
    private ?int $startupID = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $requestedDate = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $requestedTime = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Assert\Type('string')]
    private ?string $topic = null;

    #[ORM\Column(type: Types::STRING)]
    #[Assert\Length(max: 255)]
    #[Assert\Type('string')]
    private ?string $status = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $creationDate = null;

    public function getBookingID(): ?int
    {
        return $this->bookingID;
    }

    public function setBookingID(?int $bookingID): static
    {
        $this->bookingID = $bookingID;
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

    public function getMentorID(): ?int
    {
        return $this->mentorID;
    }

    public function setMentorID(?int $mentorID): static
    {
        $this->mentorID = $mentorID;
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

    public function getRequestedDate(): ?\DateTimeInterface
    {
        return $this->requestedDate;
    }

    public function setRequestedDate(?\DateTimeInterface $requestedDate): static
    {
        $this->requestedDate = $requestedDate;
        return $this;
    }

    public function getRequestedTime(): ?\DateTimeInterface
    {
        return $this->requestedTime;
    }

    public function setRequestedTime(?\DateTimeInterface $requestedTime): static
    {
        $this->requestedTime = $requestedTime;
        return $this;
    }

    public function getTopic(): ?string
    {
        return $this->topic;
    }

    public function setTopic(?string $topic): static
    {
        $this->topic = $topic;
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

}
