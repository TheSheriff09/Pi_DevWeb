<?php

namespace App\Entity;

use App\Repository\MentorFavoritesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: '`mentor_favorites`')]
class MentorFavorites
{
    #[ORM\Id]
    #[ORM\Column(name: '`id`', type: Types::INTEGER)]
    #[Assert\Type('integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(name: 'entrepreneur_id', referencedColumnName: 'id', nullable: false)]
    private ?Users $entrepreneur = null;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(name: 'mentor_id', referencedColumnName: 'id', nullable: false)]
    private ?Users $mentor = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): static
    {
        $this->id = $id;
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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

}
