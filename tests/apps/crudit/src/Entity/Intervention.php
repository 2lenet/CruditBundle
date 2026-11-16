<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * An intervention and its state in the workflow "intervention" (config/packages/workflow.yaml, lot 3.4.c).
 */
#[ORM\Entity]
class Intervention implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    private string $reference = '';

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $technician = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $status = 'to_plan';

    // The record (lot 3.3.a): key information, sections, an alert when it is late, the dates of the header
    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTime $plannedOn = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $amount = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $internalNote = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $updatedAt = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $updatedBy = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function setReference(string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    public function getTechnician(): ?string
    {
        return $this->technician;
    }

    public function setTechnician(?string $technician): self
    {
        $this->technician = $technician;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getPlannedOn(): ?\DateTime
    {
        return $this->plannedOn;
    }

    public function setPlannedOn(?\DateTime $plannedOn): self
    {
        $this->plannedOn = $plannedOn;

        return $this;
    }

    /** Days past the planned date while it is still to be done, else 0 */
    public function getDelay(): int
    {
        if ($this->plannedOn === null || !\in_array($this->status, ['to_plan', 'planned'], true)) {
            return 0;
        }

        $today = new \DateTime('today');
        $planned = (clone $this->plannedOn)->setTime(0, 0);

        return $planned < $today ? (int) $planned->diff($today)->days : 0;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(?string $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getInternalNote(): ?string
    {
        return $this->internalNote;
    }

    public function setInternalNote(?string $internalNote): self
    {
        $this->internalNote = $internalNote;

        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUpdatedBy(): ?string
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?string $updatedBy): self
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    public function __toString(): string
    {
        return $this->reference;
    }
}
