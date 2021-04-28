<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

use Lle\CruditBundle\Dto\Path;

interface CrudConfigInterface
{
    public const INDEX = "INDEX";
    public const SHOW = "SHOW";
    public const EDIT = "EDIT";
    public const NEW = "NEW";
    public const DELETE = "DELETE";

    /** @return BrickConfigInterface[][] */
    public function getBrickConfigs(): array;

    public function getDatasource(): DatasourceInterface;

    public function getController(): ?string;

    public function getName(): ?string;

    public function getTitle(): ?string;

    public function getRootRoute(): ?string;

    public function getPath(string $context = self::INDEX, array $params = []): Path;
}
