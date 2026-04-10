<?php

namespace App\Entity;

use App\Repository\SessionNotesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: '`session_notes`')]
class SessionNotes
{
    #[ORM\Id]
    #[ORM\Column(name: '`noteID`', type: Types::INTEGER)]
    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    private ?int $noteID = null;

    #[ORM\Column(name: '`sessionID`', type: Types::INTEGER)]
    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    private ?int $sessionID = null;

    #[ORM\Column(name: '`entrepreneurID`', type: Types::INTEGER)]
    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    private ?int $entrepreneurID = null;

    #[ORM\Column(name: '`satisfactionScore`', type: Types::INTEGER, nullable: true)]
    #[Assert\Type('integer')]
    private ?int $satisfactionScore = null;

    #[ORM\Column(name: '`notes`', type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Assert\Type('string')]
    private ?string $notes = null;

    #[ORM\Column(name: '`noteDate`', type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $noteDate = null;

    public function getNoteID(): ?int
    {
        return $this->noteID;
    }

    public function setNoteID(?int $noteID): static
    {
        $this->noteID = $noteID;
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

    public function getEntrepreneurID(): ?int
    {
        return $this->entrepreneurID;
    }

    public function setEntrepreneurID(?int $entrepreneurID): static
    {
        $this->entrepreneurID = $entrepreneurID;
        return $this;
    }

    public function getSatisfactionScore(): ?int
    {
        return $this->satisfactionScore;
    }

    public function setSatisfactionScore(?int $satisfactionScore): static
    {
        $this->satisfactionScore = $satisfactionScore;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function getNoteDate(): ?\DateTimeInterface
    {
        return $this->noteDate;
    }

    public function setNoteDate(?\DateTimeInterface $noteDate): static
    {
        $this->noteDate = $noteDate;
        return $this;
    }

}
