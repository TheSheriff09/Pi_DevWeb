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
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'note_id', type: Types::INTEGER)]
    #[Assert\Type('integer')]
    private ?int $noteID = null;

    #[ORM\ManyToOne(targetEntity: Session::class)]
    #[ORM\JoinColumn(name: 'sessionID', referencedColumnName: 'sessionID', nullable: false)]
    private ?Session $session = null;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(name: 'entrepreneur_id', referencedColumnName: 'id', nullable: false)]
    private ?Users $entrepreneur = null;

    #[ORM\Column(name: 'satisfaction_score', type: Types::INTEGER, nullable: true)]
    #[Assert\Type('integer')]
    private ?int $satisfactionScore = null;

    #[ORM\Column(name: 'notes', type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank]
    #[Assert\Type('string')]
    private ?string $notes = null;

    #[ORM\Column(name: 'note_date', type: Types::DATE_MUTABLE, nullable: true)]
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

    public function getSession(): ?Session
    {
        return $this->session;
    }

    public function setSession(?Session $session): static
    {
        $this->session = $session;
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
