<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Priority;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

#[ORM\Entity]
#[Vich\Uploadable]
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

    #[ORM\Column(length: 10, nullable: true, enumType: Priority::class)]
    private ?Priority $priority = null;

    /** @var list<string>|null the channels of contact: a ChoiceField of several values */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $channels = null;

    /** A file sent with VichUploaderBundle (lot 3.2.c): the mapping keeps its size, type and original name */
    #[Vich\UploadableField(mapping: 'client_attachment', fileNameProperty: 'attachmentName', size: 'attachmentSize', mimeType: 'attachmentMimeType', originalName: 'attachmentOriginalName')]
    private ?File $attachmentFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $attachmentName = null;

    #[ORM\Column(nullable: true)]
    private ?int $attachmentSize = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $attachmentMimeType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $attachmentOriginalName = null;

    /** Changed with the file: Doctrine sees a change when only a file is sent, and Vich stores it */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $attachmentUpdatedAt = null;

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

    public function getPriority(): ?Priority
    {
        return $this->priority;
    }

    public function setPriority(?Priority $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    /** @return list<string>|null */
    public function getChannels(): ?array
    {
        return $this->channels;
    }

    /** @param list<string>|null $channels */
    public function setChannels(?array $channels): self
    {
        $this->channels = $channels;

        return $this;
    }

    public function getAttachmentFile(): ?File
    {
        return $this->attachmentFile;
    }

    public function setAttachmentFile(?File $attachmentFile): self
    {
        $this->attachmentFile = $attachmentFile;
        if (null !== $attachmentFile) {
            $this->attachmentUpdatedAt = new \DateTime();
        }

        return $this;
    }

    public function getAttachmentName(): ?string
    {
        return $this->attachmentName;
    }

    public function setAttachmentName(?string $attachmentName): self
    {
        $this->attachmentName = $attachmentName;

        return $this;
    }

    public function getAttachmentSize(): ?int
    {
        return $this->attachmentSize;
    }

    public function setAttachmentSize(?int $attachmentSize): self
    {
        $this->attachmentSize = $attachmentSize;

        return $this;
    }

    public function getAttachmentMimeType(): ?string
    {
        return $this->attachmentMimeType;
    }

    public function setAttachmentMimeType(?string $attachmentMimeType): self
    {
        $this->attachmentMimeType = $attachmentMimeType;

        return $this;
    }

    public function getAttachmentOriginalName(): ?string
    {
        return $this->attachmentOriginalName;
    }

    public function setAttachmentOriginalName(?string $attachmentOriginalName): self
    {
        $this->attachmentOriginalName = $attachmentOriginalName;

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
