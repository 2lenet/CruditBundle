<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

use Lle\CruditBundle\Datasource\DatasourceParams;
use Lle\CruditBundle\Dto\Path;
use Lle\CruditBundle\Exporter\ExportParams;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

interface CrudConfigInterface
{
    public const INDEX = "INDEX";
    public const SHOW = "SHOW";
    public const EDIT = "EDIT";
    public const NEW = "NEW";
    public const DELETE = "DELETE";
    public const EXPORT = "EXPORT";
    public const ACTION_LIST = "list";
    public const ACTION_SHOW = "show";
    public const ACTION_EDIT = "edit";
    public const ACTION_ADD = "add";
    public const ACTION_DELETE = "delete";
    public const ACTION_EXPORT = "export";
    public const AVERAGE = "AVG";
    public const COUNT = "COUNT";
    public const SUM = "SUM";
    public const EXPRESSION = "EXPRESSION";

    public function getFields(string $key): array;

    public function getFilterset(): ?FilterSetInterface;

    public function getListActions(): array;

    public function getItemActions(): array;

    public function getShowActions(): array;

    public function getDatasource(): DatasourceInterface;

    public function getDatasourceParams(Request $request, ?string $sessionKey = null): DatasourceParams;

    public function getDatasourceParamsKey(): string;

    public function getController(): ?string;

    public function getName(): ?string;

    public function getTitle(string $key): ?string;

    public function getPath(string $context = self::INDEX, array $params = []): Path;

    /** @return BrickConfigInterface[][] */
    public function getBrickConfigs(): array;

    public function getTabs(): array;

    public function getForm(object $resource): ?FormInterface;

    public function getDefaultSort(): array;

    public function getExportParams(string $format): ExportParams;

    public function getAfterEditPath(): Path;

    public function getNbItems(): int;

    public function getChoicesNbItems(): array;

    public function getTranslationDomain(): string;

    public function fieldsToUpdate(int|string $id): array;

    public function eipToUpdate(int|string $id): array;

    public function getTotalFields(): array;
    
    public function getRootRoute(): ?string;

    public function getListAutoRefresh(?int $interval = null): ?int;

    public function getShowAutoRefresh(?int $interval = null): ?int;
}
