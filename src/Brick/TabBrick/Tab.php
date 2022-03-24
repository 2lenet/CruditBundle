<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\TabBrick;

use Lle\CruditBundle\Contracts\BrickConfigInterface;

class Tab
{
    /** @var BrickConfigInterface[] */
    private $bricks;

    /** @var string */
    private $label;

    private $role = null;

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
