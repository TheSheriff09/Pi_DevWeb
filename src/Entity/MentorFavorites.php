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

    #[ORM\Column(name: '`entrepreneurID`', type: Types::INTEGER)]
    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    private ?int $entrepreneurID = null;

    #[ORM\Column(name: '`mentorID`', type: Types::INTEGER)]
    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    private ?int $mentorID = null;

    #[ORM\Column(name: '`createdAt`', type: Types::DATETIME_MUTABLE)]
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
