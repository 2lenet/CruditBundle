<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

use Lle\CruditBundle\Dto\Path;
use Lle\CruditBundle\Exporter\ExportParams;

interface CrudConfigInterface
{
    public const INDEX = "INDEX";
    public const SHOW = "SHOW";
    public const EDIT = "EDIT";
    public const NEW = "NEW";
    public const DELETE = "DELETE";
    public const EXPORT = "EXPORT";

    /** @return BrickConfigInterface[][] */
    public function getBrickConfigs(): array;

    public function getDatasource(): DatasourceInterface;

    public function getController(): ?string;

    public function getName(): ?string;

    public function getTitle(string $key): ?string;

    public function getRootRoute(): ?string;

    public function getPath(string $context = self::INDEX, array $params = []): Path;

    public function getDefaultSort(): array;

    public function getExportParams(string $format): ExportParams;

    public function getAfterEditPath(): Path;

    public function getChoicesNbItems(): array;
}
