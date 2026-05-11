<?php

namespace App\Entity;

use App\Repository\ResponsesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: '`responses`')]
class Responses
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\Type('integer')]
    /** @phpstan-ignore-next-line */
    private ?int $id = null;

    #[ORM\Column(name: 'content', type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Assert\Type('string')]
    private ?string $content = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\ManyToOne(targetEntity: Reclamations::class)]
    #[ORM\JoinColumn(name: 'reclamation_id', referencedColumnName: 'id', nullable: false)]
    private ?Reclamations $reclamation = null;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(name: 'responder_user_id', referencedColumnName: 'id', nullable: false)]
    private ?Users $responder = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): static
    {
        $this->content = $content;
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

    public function getReclamation(): ?Reclamations
    {
        return $this->reclamation;
    }

    public function setReclamation(?Reclamations $reclamation): static
    {
        $this->reclamation = $reclamation;
        return $this;
    }

    public function getResponder(): ?Users
    {
        return $this->responder;
    }

    public function setResponder(?Users $responder): static
    {
        $this->responder = $responder;
        return $this;
    }

}
