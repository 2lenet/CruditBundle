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

    #[ORM\Column(length: 20)]
    private string $status = 'to_plan';

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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function __toString(): string
    {
        return $this->reference;
    }
}
