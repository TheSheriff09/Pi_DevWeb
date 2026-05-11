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
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'booking_id', type: Types::INTEGER)]
    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    private ?int $bookingID = null;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(name: 'entrepreneur_id', referencedColumnName: 'id', nullable: false)]
    private ?Users $entrepreneur = null;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(name: 'mentor_id', referencedColumnName: 'id', nullable: false)]
    private ?Users $mentor = null;

    #[ORM\ManyToOne(targetEntity: Startup::class)]
    #[ORM\JoinColumn(name: 'startup_id', referencedColumnName: 'startup_id', nullable: true)]
    private ?Startup $startup = null;

    #[ORM\Column(name: 'requested_date', type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $requestedDate = null;

    #[ORM\Column(name: 'requested_time', type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $requestedTime = null;

    #[ORM\Column(name: '`topic`', type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Assert\Type('string')]
    private ?string $topic = null;

    #[ORM\Column(name: '`status`', type: Types::STRING)]
    #[Assert\Length(max: 255)]
    #[Assert\Type('string')]
    private ?string $status = null;

    #[ORM\Column(name: 'creation_date', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $creationDate = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private ?Users $user = null;

    public function getBookingID(): ?int
    {
        return $this->bookingID;
    }

    public function setBookingID(?int $bookingID): static
    {
        $this->bookingID = $bookingID;
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

    public function getMentor(): ?Users
    {
        return $this->mentor;
    }

    public function setMentor(?Users $mentor): static
    {
        $this->mentor = $mentor;
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
