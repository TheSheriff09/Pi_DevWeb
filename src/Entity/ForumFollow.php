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
    
    /** @phpstan-ignore-next-line */
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(name: 'follower_id', referencedColumnName: 'id', nullable: false)]
    private ?Users $follower = null;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(name: 'following_id', referencedColumnName: 'id', nullable: false)]
    private ?Users $following = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFollower(): ?Users
    {
        return $this->follower;
    }

    public function setFollower(?Users $follower): static
    {
        $this->follower = $follower;
        return $this;
    }

    public function getFollowing(): ?Users
    {
        return $this->following;
    }

    public function setFollowing(?Users $following): static
    {
        $this->following = $following;
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
