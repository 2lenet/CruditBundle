<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\ShowBrick;

use Lle\CruditBundle\Brick\AbstractBasicBrickFactory;
use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Dto\BrickView;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\FieldView;
use Lle\CruditBundle\Exception\CruditException;
use Lle\CruditBundle\Field\AbstractField;
use Lle\CruditBundle\Registry\FieldRegistry;
use Lle\CruditBundle\Resolver\ResourceResolver;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The record (lot 3.3.a, brick/show_item): an alert (getShowAlert()), the key information (getShowHighlights()), then
 * the fields, those of no group in a card without title, each group in a section that folds (the first
 * getShowNumberFieldGroupsOpened() open, the others folded). A section has getShowColumns() fields per line, or its
 * own number (getShowSectionColumns()); a field takes more with its option span, a long text the whole line. The
 * sections are in 2 columns on a wide screen as soon as there are 2, a wide one (getShowWideSections()) alone on its
 * line. The status is beside the title
 * (ShowStatus): it is left out of its section. When the element was created and modified is in the header too.
 */
class ShowFactory extends AbstractBasicBrickFactory
{
    public function __construct(
        ResourceResolver $resourceResolver,
        RequestStack $requestStack,
        private readonly ShowStatus $showStatus,
        private readonly FieldRegistry $fieldRegistry,
    ) {
        parent::__construct($resourceResolver, $requestStack);
    }

    public function support(BrickConfigInterface $brickConfigurator): bool
    {
        return (ShowConfig::class === get_class($brickConfigurator));
    }

    public function buildView(BrickConfigInterface $brickConfigurator): BrickView
    {
        $view = new BrickView($brickConfigurator);
        if ($brickConfigurator instanceof ShowConfig) {
            $crudConfig = $brickConfigurator->getCrudConfig();
            $datasource = $brickConfigurator->getDataSource();
            $item = $datasource->get($this->getRequest()->attributes->get('id'));

            $resource = null;
            $sections = [];
            $highlights = [];
            $alert = null;
            if ($item !== null) {
                $resource = $this->resourceResolver->resolve(
                    $item,
                    $this->withoutStatus($brickConfigurator, $brickConfigurator->getFields()),
                    $datasource,
                    $crudConfig
                );
                $sections = $this->getSections($resource->getFields(), $crudConfig);
                $keyFields = $crudConfig->getShowHighlights();
                if ($keyFields !== []) {
                    $highlights = $this->resourceResolver->resolve($item, $keyFields, $datasource, $crudConfig)
                        ->getFields();
                }
                $alert = $crudConfig->getShowAlert($item);
            }

            $view
                ->setTemplate($brickConfigurator->getTemplate() ?? '@LleCrudit/brick/show_item')
                ->setConfig($brickConfigurator->getConfig($this->getRequest()))
                ->setData([
                    'resource' => $resource,
                    'auto_refresh' => $crudConfig->getShowAutoRefresh(),
                    'number_field_groups_opened' => $crudConfig->getShowNumberFieldGroupsOpened(),
                    'sections' => $sections,
                    'highlights' => $highlights,
                    'alert' => $alert,
                    'section_columns' => \count($sections) > 1 ? 2 : 1,
                ]);
        }

        return $view;
    }

    /**
     * The fields without the status, shown beside the title by the header of the page.
     *
     * @param array<Field|Field[]> $fields
     * @return array<Field|Field[]>
     */
    private function withoutStatus(ShowConfig $brickConfig, array $fields): array
    {
        $crudConfig = $brickConfig->getCrudConfig();
        $status = $brickConfig->getPageKey() === CrudConfigInterface::SHOW && $this->showStatus->hasHeader($crudConfig)
            ? $this->showStatus->find($crudConfig)
            : null;
        if ($status === null) {
            return $fields;
        }

        $name = $status->getName();
        $kept = static fn (Field $field): bool => $field->getName() !== $name;
        $result = [];
        foreach ($fields as $key => $field) {
            if (\is_array($field)) {
                $result[$key] = array_values(array_filter($field, $kept));
            } elseif ($kept($field)) {
                $result[$key] = $field;
            }
        }

        return $result;
    }

    /**
     * The sections of the record: a group of getFields(SHOW) is a section with its title (its key, translated),
     * the fields of no group around them make a card without title. A group whose fields are all refused (their
     * role) is not shown; it still counts for getShowNumberFieldGroupsOpened(), so that the same sections are open
     * whatever the rights of the user.
     *
     * @param array<FieldView|FieldView[]> $fields
     * @return list<array{title: ?string, id: ?string, columns: int, open: bool, wide: bool, fields: list<array{view: FieldView, span: int|string|null}>}>
     */
    private function getSections(array $fields, CrudConfigInterface $crudConfig): array
    {
        $columns = $this->checkColumns($crudConfig->getShowColumns(), 'getShowColumns()');
        $sectionColumns = $crudConfig->getShowSectionColumns();
        $wide = $crudConfig->getShowWideSections();
        $opened = $crudConfig->getShowNumberFieldGroupsOpened();

        $sections = [];
        $loose = [];
        $groups = 0;
        foreach ($fields as $key => $field) {
            if (!\is_array($field)) {
                $loose[] = $field;
                continue;
            }

            if ($loose !== []) {
                $sections[] = $this->section(null, $columns, true, false, $loose);
                $loose = [];
            }
            ++$groups;
            if ($field === []) {
                continue;
            }
            $sections[] = $this->section(
                (string) $key,
                isset($sectionColumns[$key])
                    ? $this->checkColumns($sectionColumns[$key], 'getShowSectionColumns()[' . $key . ']')
                    : $columns,
                $opened === null || $groups <= $opened,
                \in_array((string) $key, $wide, true),
                $field,
            );
        }
        if ($loose !== []) {
            $sections[] = $this->section(null, $columns, true, false, $loose);
        }

        return $sections;
    }

    /**
     * @param FieldView[] $fields
     * @return array{title: ?string, id: ?string, columns: int, open: bool, wide: bool, fields: list<array{view: FieldView, span: int|string|null}>}
     */
    private function section(?string $title, int $columns, bool $open, bool $wide, array $fields): array
    {
        return [
            'title' => $title,
            'id' => $title === null ? null : 'crudit-show-' . trim((string) preg_replace('/[^A-Za-z0-9_-]+/', '-', $title), '-'),
            'columns' => $columns,
            'open' => $open,
            'wide' => $wide,
            'fields' => array_map(fn (FieldView $view): array => [
                'view' => $view,
                'span' => $this->getSpan($view),
            ], array_values($fields)),
        ];
    }

    /** The option span of the field, else the whole line for a long text (AbstractField::isWide()) */
    private function getSpan(FieldView $view): int|string|null
    {
        $options = $view->getOptions();
        $span = \is_array($options) ? ($options['span'] ?? null) : null;
        if ($span !== null) {
            return $span;
        }

        $type = $view->getField()->getType();
        try {
            $field = $type !== null ? $this->fieldRegistry->get($type) : null;
        } catch (CruditException) {
            $field = null;
        }

        return $field instanceof AbstractField && $field->isWide() ? 'full' : null;
    }

    private function checkColumns(mixed $columns, string $source): int
    {
        if (!\is_int($columns) || $columns < 1 || $columns > 4) {
            throw new CruditException(sprintf(
                'The fields per line of a record are 1 to 4, %s given by %s.',
                get_debug_type($columns) === 'int' ? $columns : get_debug_type($columns),
                $source,
            ));
        }

        return $columns;
    }
}
