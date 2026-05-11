<?php

namespace App\Entity;

use App\Repository\ReclamationsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: '`reclamations`')]
#[ORM\Index(columns: ['requested_id'], name: 'idx_reclamation_requester')]
#[ORM\Index(columns: ['target_id'], name: 'idx_reclamation_target')]
class Reclamations
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\Type('integer')]
    /** @phpstan-ignore-next-line */
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[Assert\Type('string')]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank]
    #[Assert\Type('string')]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Assert\Type('string')]
    private ?string $status = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(name: 'requested_id', referencedColumnName: 'id', nullable: false)]
    private ?Users $requestedBy = null;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(name: 'target_id', referencedColumnName: 'id', nullable: true)]
    private ?Users $targetUser = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getRequestedBy(): ?Users
    {
        return $this->requestedBy;
    }

    public function setRequestedBy(?Users $requestedBy): static
    {
        $this->requestedBy = $requestedBy;
        return $this;
    }

    public function getTargetUser(): ?Users
    {
        return $this->targetUser;
    }

    public function setTargetUser(?Users $targetUser): static
    {
        $this->targetUser = $targetUser;
        return $this;
    }

}
