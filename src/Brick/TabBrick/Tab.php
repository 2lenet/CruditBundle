<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\TabBrick;

use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * A tab of a record (TabConfig): its label, its bricks, its role (a tab without the role is not rendered), a condition,
 * its id (in the address, ?tab=<id>: given by TabConfig from the label when not set) and a counter after its label,
 * computed on the record ("Parts 3").
 */
class Tab
{
    /** @var BrickConfigInterface[] */
    private array $bricks;

    private string $label;

    private ?string $id = null;

    /** @var callable|null fn (object $resource): ?int */
    private $count = null;

    private ?string $role = null;

    /** @var callable|null $displayIf */
    protected $displayIf = null;

    /** @var callable|null $displayIfByRequest */
    protected $displayIfByRequest = null;

    private function __construct(array $bricks)
    {
        $this->bricks = $bricks;
    }

    public static function new(string $label, array $bricks = []): self
    {
        return (new self($bricks))
            ->setLabel($label);
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function add(BrickConfigInterface $brick): self
    {
        $this->bricks[] = $brick;

        return $this;
    }

    /** @return BrickConfigInterface[] */
    public function getBricks(): array
    {
        return $this->bricks;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * The id of the tab in the address (?tab=<id>) and in the ids of the page: letters, digits, _ and -.
     */
    public function setId(?string $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * A counter after the label, computed on the record: fn (object $resource): ?int (null: no counter).
     */
    public function setCount(?callable $count): self
    {
        $this->count = $count;

        return $this;
    }

    public function getCount(?object $resource): ?int
    {
        if (null === $this->count || null === $resource) {
            return null;
        }

        return ($this->count)($resource);
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function isDisplayed(mixed $resource = null): bool
    {
        return !$this->displayIf || call_user_func($this->displayIf, $resource);
    }

    public function isDisplayedByRequest(?Request $request = null): bool
    {
        return !$this->displayIfByRequest || call_user_func($this->displayIfByRequest, $request);
    }

    public function setDisplayIf(?callable $displayIf): self
    {
        $this->displayIf = $displayIf;

        return $this;
    }

    public function setDisplayIfByRequest(?callable $displayIfByRequest): self
    {
        $this->displayIfByRequest = $displayIfByRequest;

        return $this;
    }
}
