<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: '`forum_follow`')]
class ForumFollow
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $followerId = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $followingId = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFollowerId(): ?int
    {
        return $this->followerId;
    }

    public function setFollowerId(int $followerId): static
    {
        $this->followerId = $followerId;
        return $this;
    }

    public function getFollowingId(): ?int
    {
        return $this->followingId;
    }

    public function setFollowingId(int $followingId): static
    {
        $this->followingId = $followingId;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
