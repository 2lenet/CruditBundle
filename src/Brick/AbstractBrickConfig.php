<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick;

use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Contracts\CrudConfigInterface;

abstract class AbstractBrickConfig implements BrickConfigInterface
{

    public function setCrudConfig(CrudConfigInterface $crudConfig): self
    {
        return $this;
    }

    public function getConfig(): array
    {
        return [];
    }
}
