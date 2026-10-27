<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Client
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private string $name = '';

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $email = null;

    #[ORM\Column]
    private bool $active = true;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private \DateTime $createdAt;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $website = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $amount = null;

    #[ORM\Column(nullable: true)]
    private ?int $progress = null;

    #[ORM\Column(nullable: true)]
    private ?int $budgetUsed = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $color = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(nullable: true)]
    private ?bool $blocked = null;

    /** @var array<mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $tags = null;

    #[ORM\ManyToOne(targetEntity: self::class)]
    private ?self $referrer = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

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

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): self
    {
        $this->website = $website;

        return $this;
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

    public function getProgress(): ?int
    {
        return $this->progress;
    }

    public function setProgress(?int $progress): self
    {
        $this->progress = $progress;

        return $this;
    }

    public function getBudgetUsed(): ?int
    {
        return $this->budgetUsed;
    }

    public function setBudgetUsed(?int $budgetUsed): self
    {
        $this->budgetUsed = $budgetUsed;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;

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

    public function getBlocked(): ?bool
    {
        return $this->blocked;
    }

    public function setBlocked(?bool $blocked): self
    {
        $this->blocked = $blocked;

        return $this;
    }

    /** @return array<mixed>|null */
    public function getTags(): ?array
    {
        return $this->tags;
    }

    /** @param array<mixed>|null $tags */
    public function setTags(?array $tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    public function getReferrer(): ?self
    {
        return $this->referrer;
    }

    public function setReferrer(?self $referrer): self
    {
        $this->referrer = $referrer;

        return $this;
    }

    /**
     * A rule of the business (DeleteAction): an active client is not deleted, the action stays with its reason.
     */
    public function canDelete(): bool|string
    {
        return $this->active ? 'client.reason.active' : true;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
