<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

use Lle\CruditBundle\Brick\TabBrick\TabConfig;
use Lle\CruditBundle\Datasource\DatasourceParams;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\Path;
use Lle\CruditBundle\Dto\ShowAlert;
use Lle\CruditBundle\Exporter\ExportParams;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

interface CrudConfigInterface
{
    public const INDEX = 'INDEX';
    public const SHOW = 'SHOW';
    public const EDIT = 'EDIT';
    public const NEW = 'NEW';
    public const DELETE = 'DELETE';
    public const EXPORT = 'EXPORT';
    public const SORT = 'SORT';

    public const ACTION_LIST = 'list';
    public const ACTION_SHOW = 'show';
    public const ACTION_EDIT = 'edit';
    public const ACTION_ADD = 'add';
    public const ACTION_DELETE = 'delete';
    public const ACTION_EXPORT = 'export';

    public const AVERAGE = 'AVG';
    public const COUNT = 'COUNT';
    public const SUM = 'SUM';

    public const EXPRESSION = 'EXPRESSION';

    public const BASIC_FIELDS_KEYS = [
        CrudConfigInterface::INDEX,
        CrudConfigInterface::SHOW,
        CrudConfigInterface::EDIT,
        CrudConfigInterface::NEW,
        CrudConfigInterface::DELETE,
        CrudConfigInterface::EXPORT,
    ];
    public const ADDITIONAL_FIELDS_KEYS = [];

    public const BASIC_ACTIONS_KEYS = [
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

    /**
     * Replaces the offset kept in the session by getDatasourceParams(): the next visit starts at this row.
     */
    public function setDatasourceOffset(Request $request, int $offset, ?string $sessionKey = null): void;

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

    public function getDefaultAutocompleteSort(): ?array;

    public function getExportParams(string $format): ExportParams;

    public function getAfterEditPath(): ?Path;

    public function getNbItems(): int;

    public function getChoicesNbItems(): array;

    public function getTranslationDomain(): string;

    public function fieldsToUpdate(int|string $id): array;

    public function eipToUpdate(int|string $id): array;

    public function getTotalFields(): array;

    public function getRootRoute(): ?string;

    public function getSortableField(): ?string;

    public function getSortableUrl(): ?Path;

    public function getListAutoRefresh(): ?int;

    public function getShowAutoRefresh(): ?int;

    public function getShowNumberFieldGroupsOpened(): ?int;

    public function setParameterBag(ParameterBagInterface $parameterBag): void;

    public function getTabConfig(): ?TabConfig;

    public function getSublistTotalFields(): array;

    /**
     * Most fields on a line of the forms of this CRUD (1 to 4): fewer when the screen is narrow, 1 on a phone.
     */
    public function getFormColumns(): int;

    /** Fields per line in a section of a record, 1 to 4 (lle_crudit.show_columns: 4) */
    public function getShowColumns(): int;

    /**
     * Fields per line of some sections of the record, by the key of the section in getFields(SHOW); the others take
     * getShowColumns().
     *
     * @return array<string, int>
     */
    public function getShowSectionColumns(): array;

    /**
     * Sections of the record that take the whole line, by their key in getFields(SHOW): the sections are in 2
     * columns on a wide screen as soon as there are 2, these ones are alone on their line (none by default).
     *
     * @return list<string>
     */
    public function getShowWideSections(): array;

    /**
     * Key information of the record: a band of values under its header (none by default).
     *
     * @return Field[]
     */
    public function getShowHighlights(): array;

    /** Under the title of the record (none by default) */
    public function getShowSubtitle(object $resource): ?string;

    /** A message above the record, a text and a tone (none by default): never the colour alone, as getCssClass() */
    public function getShowAlert(object $resource): ?ShowAlert;

    /** The first workflow field of the record is its status, beside its title, rather than in its section */
    public function isShowStatusInHeader(): bool;
}
