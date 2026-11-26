<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\TabBrick;

use Lle\CruditBundle\Dto\BrickView;

/**
 * A tab as rendered: its id, its label, its counter and the views of its bricks (only the tabs granted and shown).
 */
class TabView
{
    /** @var BrickView[] */
    private array $bricks = [];
    private ?string $role = null;
    private ?int $count = null;

    public function __construct(
        private string $label,
        private string $id,
    ) {
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function add(BrickView $brickView): self
    {
        $this->bricks[] = $brickView;

        return $this;
    }

    /** @return BrickView[] */
    public function getViews(): array
    {
        return $this->bricks;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function setCount(?int $count): self
    {
        $this->count = $count;

        return $this;
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
}
