<?php

namespace App\Entity;

use App\Repository\UsersRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;

#[ORM\Entity]
#[ORM\Table(name: '`users`')]
#[Vich\Uploadable]
class Users
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\Type('integer')]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[Assert\Type('string')]
    private ?string $fullName = null;

    #[ORM\Column(type: Types::STRING, length: 150)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    #[Assert\Type('string')]
    private ?string $email = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Assert\Type('string')]
    private ?string $passwordHash = null;

    #[ORM\Column(type: Types::STRING)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Assert\Type('string')]
    private ?string $role = null;

    #[ORM\Column(type: Types::STRING)]
    #[Assert\Length(max: 255)]
    #[Assert\Type('string')]
    private ?string $status = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Type('string')]
    private ?string $mentorExpertise = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    #[Assert\Type('string')]
    private ?string $evaluatorLevel = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Type('string')]
    private ?string $faceEncoding = null;

    #[Vich\UploadableField(mapping: 'mentors_images', fileNameProperty: 'imageName', size: 'imageSize')]
    private ?File $imageFile = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $imageName = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $imageSize = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $forumBio = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $forumImage = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(?string $fullName): static
    {
        $this->fullName = $fullName;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getPasswordHash(): ?string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(?string $passwordHash): static
    {
        $this->passwordHash = $passwordHash;
        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): static
    {
        $this->role = $role;
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

    public function getMentorExpertise(): ?string
    {
        return $this->mentorExpertise;
    }

    public function setMentorExpertise(?string $mentorExpertise): static
    {
        $this->mentorExpertise = $mentorExpertise;
        return $this;
    }

    public function getEvaluatorLevel(): ?string
    {
        return $this->evaluatorLevel;
    }

    public function setEvaluatorLevel(?string $evaluatorLevel): static
    {
        $this->evaluatorLevel = $evaluatorLevel;
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

    public function getFaceEncoding(): ?string
    {
        return $this->faceEncoding;
    }

    public function setFaceEncoding(?string $faceEncoding): static
    {
        $this->faceEncoding = $faceEncoding;
        return $this;
    }

    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageName(?string $imageName): void
    {
        $this->imageName = $imageName;
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function setImageSize(?int $imageSize): void
    {
        $this->imageSize = $imageSize;
    }

    public function getImageSize(): ?int
    {
        return $this->imageSize;
    }
    
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isTwoFactorEmailEnabled = false;

    #[ORM\Column(type: Types::STRING, length: 10, nullable: true)]
    private ?string $twoFactorEmailCode = null;

    #[ORM\Column(type: Types::FLOAT, options: ['default' => 0])]
    private float $riskScore = 0;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['default' => 'NORMAL'])]
    private string $riskLevel = 'NORMAL';

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function isTwoFactorEmailEnabled(): bool
    {
        return $this->isTwoFactorEmailEnabled;
    }

    public function setIsTwoFactorEmailEnabled(bool $isTwoFactorEmailEnabled): static
    {
        $this->isTwoFactorEmailEnabled = $isTwoFactorEmailEnabled;
        return $this;
    }

    public function getTwoFactorEmailCode(): ?string
    {
        return $this->twoFactorEmailCode;
    }

    public function setTwoFactorEmailCode(?string $twoFactorEmailCode): static
    {
        $this->twoFactorEmailCode = $twoFactorEmailCode;
        return $this;
    }

    public function getRiskScore(): float
    {
        return $this->riskScore;
    }

    public function setRiskScore(float $riskScore): static
    {
        $this->riskScore = $riskScore;
        return $this;
    }

    public function getRiskLevel(): string
    {
        return $this->riskLevel;
    }

    public function setRiskLevel(string $riskLevel): static
    {
        $this->riskLevel = $riskLevel;
        return $this;
    }

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $gamificationPoints = 0;

    public function getGamificationPoints(): int
    {
        return $this->gamificationPoints;
    }

    public function setGamificationPoints(int $gamificationPoints): static
    {
        $this->gamificationPoints = $gamificationPoints;
        return $this;
    }

    public function getForumBio(): ?string
    {
        return $this->forumBio;
    }

    public function setForumBio(?string $forumBio): static
    {
        $this->forumBio = $forumBio;
        return $this;
    }

    public function getForumImage(): ?string
    {
        return $this->forumImage;
    }

    public function setForumImage(?string $forumImage): static
    {
        $this->forumImage = $forumImage;
        return $this;
    }
}
