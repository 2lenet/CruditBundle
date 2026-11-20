<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A set of filters that a user saved under a name, for one list (lot 3.1.f): the filters only, no search, no sort.
 *
 * The owner is the identifier of the user (`getUserIdentifier()`), not an association: the class of the users belongs
 * to the project, and the table is the same everywhere (UPGRADE-2.0.md gives the migration). `criteria` has the shape
 * of what the session keeps: `{<filter id>: {op, value, to?, preset?}}`. The unique index (owner, filterset, name) also serves
 * the search by (owner, filterset), so there is no other index.
 */
#[ORM\Entity]
#[ORM\Table(name: 'crudit_saved_filter')]
#[ORM\UniqueConstraint(name: 'crudit_saved_filter_name', columns: ['owner', 'filterset', 'name'])]
class SavedFilter
{
    public const int NAME_LENGTH = 100;

    /** The identifier of a user longer than this is not saved (the feature is off for that user), never cut: two users would share their sets. */
    public const int OWNER_LENGTH = 255;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: self::OWNER_LENGTH)]
    private string $owner;

    #[ORM\Column(length: 100)]
    private string $filterset;

    #[ORM\Column(length: self::NAME_LENGTH)]
    private string $name;

    /** @var array<string, array<string, string>> */
    #[ORM\Column(type: Types::JSON)]
    private array $criteria = [];

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    /**
     * @param array<string, array<string, string>> $criteria
     */
    public function __construct(string $owner, string $filterset, string $name, array $criteria, ?\DateTimeImmutable $now = null)
    {
        $this->owner = $owner;
        $this->filterset = $filterset;
        $this->name = $name;
        $this->criteria = $criteria;
        $this->createdAt = $this->updatedAt = $now ?? new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOwner(): string
    {
        return $this->owner;
    }

    public function getFilterset(): string
    {
        return $this->filterset;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function getCriteria(): array
    {
        return $this->criteria;
    }

    /**
     * @param array<string, array<string, string>> $criteria
     */
    public function setCriteria(array $criteria, ?\DateTimeImmutable $now = null): static
    {
        $this->criteria = $criteria;
        $this->updatedAt = $now ?? new \DateTimeImmutable();

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
