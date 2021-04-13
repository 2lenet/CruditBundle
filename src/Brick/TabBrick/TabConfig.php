<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\TabBrick;

use Lle\CruditBundle\Brick\AbstractBrickConfig;
use Lle\CruditBundle\Contracts\BrickConfigInterface;

class TabConfig extends AbstractBrickConfig
{
    /** @var Tab[] */
    private $tabs = [];

    public static function new(array $options = []): self
    {
        return new self($options);
    }

    public function __construct(array $options)
    {
        $this->tabs = $options['tabs'] ?? [];
    }

    /** @return Tab[] */
    public function getTabs(): array
    {
        return $this->tabs;
    }

    public function add(string $label, BrickConfigInterface $brickConfig): self
    {
        $this->addTab(Tab::new($label, [$brickConfig]));
        return $this;
    }

    /** @param BrickConfigInterface[] $bricksConfig */
    public function adds(string $label, array $bricksConfig): self
    {
        $this->addTab(Tab::new($label, $bricksConfig));
        return $this;
    }

    public function addTab(Tab $tab): self
    {
        $this->tabs[] = $tab;
        return $this;
    }

    public function getChildren(): array
    {
        $brickConfigs = [];
        foreach ($this->tabs as $tab) {
            foreach ($tab->getBricks() as $brick) {
                $brickConfigs[] = $brick;
                foreach ($brick->getChildren() as $childBrick) {
                    $brickConfigs[] = $childBrick;
                }
            }
        }
        return $brickConfigs;
    }
}
