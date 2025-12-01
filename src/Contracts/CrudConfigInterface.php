<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

use Lle\CruditBundle\Datasource\DatasourceParams;
use Lle\CruditBundle\Dto\Path;
use Lle\CruditBundle\Exporter\ExportParams;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

interface CrudConfigInterface
{
    public const string INDEX = 'INDEX';
    public const string SHOW = 'SHOW';
    public const string EDIT = 'EDIT';
    public const string NEW = 'NEW';
    public const string DELETE = 'DELETE';
    public const string EXPORT = 'EXPORT';

    public const string ACTION_LIST = 'list';
    public const string ACTION_SHOW = 'show';
    public const string ACTION_EDIT = 'edit';
    public const string ACTION_ADD = 'add';
    public const string ACTION_DELETE = 'delete';
    public const string ACTION_EXPORT = 'export';

    public const string AVERAGE = 'AVG';
    public const string COUNT = 'COUNT';
    public const string SUM = 'SUM';

    public const string EXPRESSION = 'EXPRESSION';

    public const array BASIC_FIELDS_KEYS = [
        CrudConfigInterface::INDEX,
        CrudConfigInterface::SHOW,
        CrudConfigInterface::EDIT,
        CrudConfigInterface::NEW,
        CrudConfigInterface::DELETE,
        CrudConfigInterface::EXPORT,
    ];
    public const array ADDITIONAL_FIELDS_KEYS = [];

    public const array BASIC_ACTIONS_KEYS = [
        CrudConfigInterface::INDEX => CrudConfigInterface::ACTION_LIST,
        CrudConfigInterface::ACTION_SHOW => CrudConfigInterface::ACTION_SHOW,
        CrudConfigInterface::ACTION_EDIT => CrudConfigInterface::ACTION_EDIT,
        CrudConfigInterface::NEW => CrudConfigInterface::ACTION_ADD,
        CrudConfigInterface::ACTION_DELETE => CrudConfigInterface::ACTION_DELETE,
        CrudConfigInterface::ACTION_EXPORT => CrudConfigInterface::ACTION_EXPORT,
    ];

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

    public function getAfterEditPath(): ?Path;

    public function getNbItems(): int;

    public function getChoicesNbItems(): array;

    public function getTranslationDomain(): string;

    public function fieldsToUpdate(int|string $id): array;

    public function eipToUpdate(int|string $id): array;

    public function getTotalFields(): array;

    public function getRootRoute(): ?string;

    public function getListAutoRefresh(): ?int;

    public function getShowAutoRefresh(): ?int;

    public function getShowNumberFieldGroupsOpened(): ?int;

    public function setParameterBag(ParameterBagInterface $parameterBag): void;
}
