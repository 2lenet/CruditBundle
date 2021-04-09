<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick;

use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Contracts\CrudConfigInterface;

abstract class AbstractBrickConfig implements BrickConfigInterface
{
    /** @var CrudConfigInterface */
    protected $crudConfig;

    /** @var string */
    protected $pageKey;

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

    public function getConfig(): array
    {
        return [];
    }

    public function getId(): string
    {
        return md5(static::class . get_class($this->getCrudConfig()));
    }
}
