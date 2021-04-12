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

    /** @var string */
    protected $pageKey;

    /** @var string */
    protected $id;

    public function setCrudConfig(CrudConfigInterface $crudConfig): self
    {
        $this->crudConfig = $crudConfig;
        return $this;
    }

    public function getCrudConfig(): CrudConfigInterface
    {
        return $this->crudConfig;
    }

    public function getPageKey(): string
    {
        return $this->pageKey;
    }

    public function setPageKey(string $pageKey): self
    {
        $this->pageKey = $pageKey;
        return $this;
    }

    public function getDatasource(): DatasourceInterface
    {
        return $this->crudConfig->getDatasource();
    }

    public function getConfig(): array
    {
        return [];
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): BrickConfigInterface
    {
        $this->id = $id;
        return $this;
    }

    /** @return BrickConfigInterface[] */
    public function getChildren(): array
    {
        return [];
    }
}
