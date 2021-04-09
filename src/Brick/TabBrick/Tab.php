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

    public static function new(string $label, array $bricks = []): self
    {
        return (new self($bricks))
            ->setLabel($label)
            ;
    }

    private function __construct(array $bricks)
    {
        $this->bricks = $bricks;
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

    public function add(BrickConfigInterface $brick): self
    {
        $this->bricks[] = $brick;
        return $this;
    }

    /** @return BrickConfigInterface[] */
    public function getBrick(): array
    {
        return $this->bricks;
    }
}
