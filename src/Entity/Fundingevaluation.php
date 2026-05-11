<?php

namespace App\Entity;

use App\Repository\FundingevaluationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: '`fundingevaluation`')]
class Fundingevaluation
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Fundingapplication::class)]
    #[ORM\JoinColumn(name: 'funding_application_id', referencedColumnName: 'id', nullable: false)]
    private ?Fundingapplication $fundingApplication = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    private ?int $score = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[Assert\Type('string')]
    private ?string $decision = null;

    #[ORM\Column(name: 'evaluation_comments', type: Types::TEXT, nullable: true)]
    #[Assert\Type('string')]
    private ?string $evaluationComments = null;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(name: 'evaluator_id', referencedColumnName: 'id', nullable: false)]
    private ?Users $evaluator = null;

    #[ORM\Column(name: 'risk_level', type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    #[Assert\Type('string')]
    private ?string $riskLevel = null;

    #[ORM\Column(name: 'funding_category', type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    #[Assert\Type('string')]
    private ?string $fundingCategory = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, nullable: true)]
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

    public function getFundingApplication(): ?Fundingapplication
    {
        return $this->fundingApplication;
    }

    public function setFundingApplication(?Fundingapplication $fundingApplication): static
    {
        $this->fundingApplication = $fundingApplication;
        return $this;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(?int $score): static
    {
        $this->score = $score;
        return $this;
    }

    public function getDecision(): ?string
    {
        return $this->decision;
    }

    public function setDecision(?string $decision): static
    {
        $this->decision = $decision;
        return $this;
    }

    public function getEvaluationComments(): ?string
    {
        return $this->evaluationComments;
    }

    public function setEvaluationComments(?string $evaluationComments): static
    {
        $this->evaluationComments = $evaluationComments;
        return $this;
    }

    public function getEvaluator(): ?Users
    {
        return $this->evaluator;
    }

    public function setEvaluator(?Users $evaluator): static
    {
        $this->evaluator = $evaluator;
        return $this;
    }

    public function getRiskLevel(): ?string
    {
        return $this->riskLevel;
    }

    public function setRiskLevel(?string $riskLevel): static
    {
        $this->riskLevel = $riskLevel;
        return $this;
    }

    public function getFundingCategory(): ?string
    {
        return $this->fundingCategory;
    }

    public function setFundingCategory(?string $fundingCategory): static
    {
        $this->fundingCategory = $fundingCategory;
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
