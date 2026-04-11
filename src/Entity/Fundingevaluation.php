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

    #[ORM\Column(name: 'fundingApplicationId', type: Types::INTEGER)]
    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    private ?int $fundingApplicationId = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    private ?int $score = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[Assert\Type('string')]
    private ?string $decision = null;

    #[ORM\Column(name: 'evaluationComments', type: Types::TEXT, nullable: true)]
    #[Assert\Type('string')]
    private ?string $evaluationComments = null;

    #[ORM\Column(name: 'evaluatorId', type: Types::INTEGER)]
    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    private ?int $evaluatorId = null;

    #[ORM\Column(name: 'riskLevel', type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    #[Assert\Type('string')]
    private ?string $riskLevel = null;

    #[ORM\Column(name: 'fundingCategory', type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    #[Assert\Type('string')]
    private ?string $fundingCategory = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
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

    public function getFundingApplicationId(): ?int
    {
        return $this->fundingApplicationId;
    }

    public function setFundingApplicationId(?int $fundingApplicationId): static
    {
        $this->fundingApplicationId = $fundingApplicationId;
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

    public function getEvaluatorId(): ?int
    {
        return $this->evaluatorId;
    }

    public function setEvaluatorId(?int $evaluatorId): static
    {
        $this->evaluatorId = $evaluatorId;
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
