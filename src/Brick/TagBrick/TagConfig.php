<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\TagBrick;

use Lle\CruditBundle\Brick\AbstractBrickConfig;
use Lle\CruditBundle\Contracts\DatasourceInterface;

class TagConfig extends AbstractBrickConfig
{
    public function __construct(
        protected DatasourceInterface $datasource,
        protected string $editRoute,
        protected ?string $editRole = null,
        protected ?string $getTagsMethod = null,
    ) {
    }

    public static function new(
        DatasourceInterface $datasource,
        string $editRoute,
        ?string $editRole = null,
        ?string $getTagsMethod = null
    ): self {
        return new self($datasource, $editRoute, $editRole, $getTagsMethod);
    }

    public function getDatasource(): DatasourceInterface
    {
        return $this->datasource;
    }

    public function setDatasource(DatasourceInterface $datasource): self
    {
        $this->datasource = $datasource;

        return $this;
    }

    public function getEditRoute(): string
    {
        return $this->editRoute;
    }

    public function setEditRoute(string $editRoute): self
    {
        $this->editRoute = $editRoute;

        return $this;
    }

    public function getTagsMethod(): ?string
    {
        return $this->getTagsMethod;
    }

    public function setTagsMethod(?string $method): self
    {
        $this->getTagsMethod = $method;

        return $this;
    }

    public function getEditRole(): ?string
    {
        return $this->editRole;
    }

    public function setEditRole(string $editRole): self
    {
        $this->editRole = $editRole;

        return $this;
    }
}
