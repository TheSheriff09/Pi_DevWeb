<?php

namespace App\Entity;

use App\Repository\SessionTodosRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: '`session_todos`')]
class SessionTodos
{
    #[ORM\Id]
    #[ORM\Column(name: '`id`', type: Types::INTEGER)]
    #[Assert\Type('integer')]
    private ?int $id = null;

    #[ORM\Column(name: '`sessionID`', type: Types::INTEGER)]
    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    private ?int $sessionID = null;

    #[ORM\Column(name: '`taskDescription`', type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Assert\Type('string')]
    private ?string $taskDescription = null;

    #[ORM\Column(name: '`isDone`', type: Types::BOOLEAN, nullable: true)]
    private ?bool $isDone = null;

    #[ORM\Column(name: '`createdAt`', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function getSessionID(): ?int
    {
        return $this->sessionID;
    }

    public function setSessionID(?int $sessionID): static
    {
        $this->sessionID = $sessionID;
        return $this;
    }

    public function getTaskDescription(): ?string
    {
        return $this->taskDescription;
    }

    public function setTaskDescription(?string $taskDescription): static
    {
        $this->taskDescription = $taskDescription;
        return $this;
    }

    public function getIsDone(): ?bool
    {
        return $this->isDone;
    }

    public function setIsDone(?bool $isDone): static
    {
        $this->isDone = $isDone;
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
