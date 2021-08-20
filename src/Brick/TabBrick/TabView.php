<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\TabBrick;

use Lle\CruditBundle\Dto\BrickView;

class TabView
{

    /** @var BrickView[] */
    private $bricks;

    /** @var string */
    private $label;

    public function __construct(string $label)
    {
        $this->label = $label;
        $this->bricks = [];
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
        return str_replace('tab.','',$this->getLabel());
    }
}
