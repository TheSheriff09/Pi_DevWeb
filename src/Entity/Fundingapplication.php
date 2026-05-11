<?php

namespace App\Entity;

use App\Repository\FundingapplicationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: '`fundingapplication`')]
#[ORM\Index(columns: ['entrepreneur_id_int'], name: 'idx_funding_entrepreneur')]
#[ORM\Index(columns: ['project_id_int'], name: 'idx_funding_project')]
class Fundingapplication
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\Type('integer')]
    /** @phpstan-ignore-next-line */
    private ?int $id = null;

    #[ORM\Column(name: 'entrepreneur_id_int', type: Types::INTEGER, nullable: true)]
    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    private ?int $entrepreneurId = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    #[Assert\NotBlank]
    #[Assert\Positive(message: "The requested amount must be a positive value.")]
    private ?string $amount = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Assert\Type('string')]
    private ?string $status = null;

    #[ORM\Column(name: 'submission_date', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $submissionDate = null;

    #[ORM\Column(name: 'application_reason', type: Types::STRING, length: 255, nullable: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Assert\Type('string')]
    private ?string $applicationReason = null;

    #[ORM\Column(name: 'project_id_int', type: Types::INTEGER, nullable: true)]
    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    private ?int $projectId = null;

    #[ORM\Column(name: 'payment_schedule', type: Types::STRING, length: 255, nullable: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Assert\Type('string')]
    private ?string $paymentSchedule = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Assert\Type('string')]
    private ?string $attachment = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEntrepreneurId(): ?int
    {
        return $this->entrepreneurId;
    }

    public function setEntrepreneurId(?int $entrepreneurId): static
    {
        $this->entrepreneurId = $entrepreneurId;
        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(?string $amount): static
    {
        $this->amount = $amount;
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

    public function getSubmissionDate(): ?\DateTimeInterface
    {
        return $this->submissionDate;
    }

    public function setSubmissionDate(?\DateTimeInterface $submissionDate): static
    {
        $this->submissionDate = $submissionDate;
        return $this;
    }

    public function getApplicationReason(): ?string
    {
        return $this->applicationReason;
    }

    public function setApplicationReason(?string $applicationReason): static
    {
        $this->applicationReason = $applicationReason;
        return $this;
    }

    public function getProjectId(): ?int
    {
        return $this->projectId;
    }

    public function setProjectId(?int $projectId): static
    {
        $this->projectId = $projectId;
        return $this;
    }

    public function getPaymentSchedule(): ?string
    {
        return $this->paymentSchedule;
    }

    public function setPaymentSchedule(?string $paymentSchedule): static
    {
        $this->paymentSchedule = $paymentSchedule;
        return $this;
    }

    public function getAttachment(): ?string
    {
        return $this->attachment;
    }

    public function setAttachment(?string $attachment): static
    {
        $this->attachment = $attachment;
        return $this;
    }

}
