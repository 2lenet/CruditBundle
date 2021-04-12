<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick;

use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Contracts\DatasourceInterface;

abstract class AbstractBrickConfig implements BrickConfigInterface
{
    /** @var CrudConfigInterface */
    protected $crudConfig;

    public function setCrudConfig(CrudConfigInterface $crudConfig): self
    {
        $this->crudConfig = $crudConfig;
        return $this;
    }

    public function getCrudConfig(): CrudConfigInterface
    {
        return $this->crudConfig;
    }

    public function getDatasource(): DatasourceInterface
    {
        return $this->crudConfig->getDatasource();
    }

    public function getConfig(): array
    {
        return [];
    }
}
