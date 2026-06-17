<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\ListBrick;

use Lle\CruditBundle\Brick\AbstractBasicBrickFactory;
use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Contracts\GroupedTotalsInterface;
use Lle\CruditBundle\Datasource\DatasourceParams;
use Lle\CruditBundle\Datasource\RuptDateFormat;
use Lle\CruditBundle\Dto\BrickView;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\FieldView;
use Lle\CruditBundle\Dto\Path;
use Lle\CruditBundle\Dto\ResourceView;
use Lle\CruditBundle\Registry\FieldRegistry;
use Lle\CruditBundle\Resolver\ResourceResolver;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Contracts\Translation\TranslatorInterface;

class ListFactory extends AbstractBasicBrickFactory
{
    private FormFactoryInterface $formFactory;
    private FieldRegistry $fieldRegistry;
    private TranslatorInterface $translator;

    public function __construct(
        ResourceResolver $resourceResolver,
        RequestStack $requestStack,
        FormFactoryInterface $formFactory,
        FieldRegistry $fieldRegistry,
        TranslatorInterface $translator,
    ) {
        parent::__construct($resourceResolver, $requestStack);
        $this->formFactory = $formFactory;
        $this->fieldRegistry = $fieldRegistry;
        $this->translator = $translator;
    }

    public function support(BrickConfigInterface $brickConfigurator): bool
    {
        return (ListConfig::class === get_class($brickConfigurator));
    }

    public function buildView(BrickConfigInterface $brickConfigurator): BrickView
    {
        $view = new BrickView($brickConfigurator);

        if ($brickConfigurator instanceof ListConfig) {
            $batchActions = [];

            foreach ($brickConfigurator->getBatchActions() as $batchAction) {
                if ($batchAction->getForm()) {
                    $form = $this->formFactory->create($batchAction->getForm());
                    $batchActions[] = ['action' => $batchAction, 'form' => $form->createView()];
                } else {
                    $batchActions[] = ['action' => $batchAction, 'form' => ''];
                }
            }

            $view
                ->setTemplate($brickConfigurator->getTemplate() ?? '@LleCrudit/brick/list_items')
                ->setConfig($brickConfigurator->getConfig($this->getRequest()))
                ->setPath($this->getPath($brickConfigurator))
                ->setData([
                    'lines' => $this->getLines($brickConfigurator),
                    'totals' => $this->getTotals($brickConfigurator),
                    'grouped_totals' => $this->getGroupedTotals($brickConfigurator),
                    'batch_actions' => $batchActions,
                    'auto_refresh' => $brickConfigurator->getCrudConfig()->getListAutoRefresh(),
                ]);
        }

        return $view;
    }

    public function getPath(BrickConfigInterface $brickConfig): Path
    {
        return $brickConfig->getCrudConfig()->getPath(
            'brick',
            array_merge($this->getRequestParameters(), ['idBrick' => $brickConfig->getId(), '_format' => 'html'])
        );
    }

    /** @return ResourceView[] */
    protected function getLines(ListConfig $brickConfigurator): array
    {
        $lines = [];

        $dsParams = $brickConfigurator->getDatasourceParams();
        $dsParams->setCount($brickConfigurator->getDatasource()->count($dsParams));
        $this->prependRuptureSorts($dsParams, $brickConfigurator->getFields());
        $resources = $brickConfigurator->getDatasource()->list($dsParams);

        foreach ($resources as $resource) {
            $lines[] = $this->resourceResolver->resolve(
                $resource,
                $this->getFields($brickConfigurator),
                $brickConfigurator->getDatasource(),
                $brickConfigurator->getCrudConfig()
            );
        }

        return $this->annotateRuptureBreaks($lines);
    }

    /** @param ResourceView[] $lines @return ResourceView[] */
    protected function annotateRuptureBreaks(array $lines): array
    {
        $prevKey1 = null;
        $prevKey2 = null;

        foreach ($lines as $line) {
            $ruptFieldView1 = $this->findRuptFieldView($line, 1);
            $ruptFieldView2 = $this->findRuptFieldView($line, 2);

            if ($ruptFieldView1 !== null) {
                $key1 = $this->resolveGroupKey($line->getResource(), $ruptFieldView1->getField());
                if ($key1 !== $prevKey1) {
                    $display = $key1 === ''
                        ? $this->resolveNullLabel($ruptFieldView1->getField())
                        : $this->resolveGroupDisplay($line->getResource(), $ruptFieldView1->getField(), $ruptFieldView1);
                    $line->setRuptGroup1($display, $key1, $ruptFieldView1->getField()->getRuptCssClass());
                    $prevKey1 = $key1;
                    $prevKey2 = null;
                }
            }

            if ($ruptFieldView2 !== null) {
                $key2 = $this->resolveGroupKey($line->getResource(), $ruptFieldView2->getField());
                if ($key2 !== $prevKey2) {
                    $display = $key2 === ''
                        ? $this->resolveNullLabel($ruptFieldView2->getField())
                        : $this->resolveGroupDisplay($line->getResource(), $ruptFieldView2->getField(), $ruptFieldView2);
                    $line->setRuptGroup2($display, $key2, $ruptFieldView2->getField()->getRuptCssClass());
                    $prevKey2 = $key2;
                }
            }
        }

        return $lines;
    }

    protected function resolveNullLabel(Field $field): string
    {
        if ($field->getRuptNullLabel() !== null) {
            return $this->translator->trans($field->getRuptNullLabel(), [], 'LleCruditBundle');
        }

        $fieldLabel = $this->translator->trans($field->getLabel(), [], 'messages');

        return $this->translator->trans('crudit.rupture.null_label', ['%field%' => $fieldLabel], 'LleCruditBundle');
    }

    protected function resolveGroupDisplay(object $resource, Field $field, FieldView $fieldView): string
    {
        $displayFormat = $field->getRuptDateDisplayFormat();
        if ($displayFormat !== null) {
            $value = $this->resolveNestedValue($resource, $field->getName());
            if ($value instanceof \DateTimeInterface) {
                return $value->format($displayFormat);
            }
        }

        return (string) $fieldView->getValue();
    }

    /** @param Field[] $fields */
    protected function prependRuptureSorts(DatasourceParams $dsParams, array $fields): void
    {
        $rupt1 = null;
        $rupt2 = null;

        foreach ($fields as $field) {
            if ($field->getRuptGroup() === 1) {
                $rupt1 = $field;
            } elseif ($field->getRuptGroup() === 2) {
                $rupt2 = $field;
            }
        }

        if ($rupt1 === null) {
            return;
        }

        $ruptFieldNamesToFilter = [];
        foreach ([$rupt1, $rupt2] as $rupt) {
            if ($rupt !== null && $rupt->getRuptDateFormat() === null) {
                $ruptFieldNamesToFilter[] = $rupt->getName();
            }
        }

        $ruptSorts = [[$this->buildRuptSortExpr($rupt1), 'ASC']];
        if ($rupt2 !== null) {
            $ruptSorts[] = [$this->buildRuptSortExpr($rupt2), 'ASC'];
        }

        $remainingSorts = array_values(array_filter(
            $dsParams->getSorts(),
            static fn(mixed $sort): bool => !is_array($sort)
                || !in_array((string) ($sort[0] ?? ''), $ruptFieldNamesToFilter, true)
        ));

        $dsParams->setSorts($rupt1->isRuptSortPriority()
            ? array_merge($ruptSorts, $remainingSorts)
            : array_merge($remainingSorts, $ruptSorts));
    }

    protected function buildRuptSortExpr(Field $field): string
    {
        $dateFormat = $field->getRuptDateFormat();
        if ($dateFormat !== null) {
            $sqlFormat = RuptDateFormat::toSql($dateFormat);

            return "DATE_FORMAT(root.{$field->getName()}, '$sqlFormat')";
        }

        return $field->getName();
    }

    protected function findRuptFieldView(ResourceView $line, int $level): ?FieldView
    {
        foreach ($line->getFields() as $fieldView) {
            if ($fieldView->getField()->getRuptGroup() === $level) {
                return $fieldView;
            }
        }

        return null;
    }

    protected function resolveGroupKey(object $resource, Field $field): string
    {
        $value = $this->resolveNestedValue($resource, $field->getName());

        if ($value === null) {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format($field->getRuptDateFormat() ?? 'Y-m-d H:i:s');
        }

        $accessor = PropertyAccess::createPropertyAccessor();
        if (is_object($value) && $accessor->isReadable($value, 'id')) {
            return (string) $accessor->getValue($value, 'id');
        }

        return is_scalar($value) ? (string) $value : '';
    }

    private function resolveNestedValue(object $resource, string $fieldName): mixed
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $value = $resource;
        foreach (explode('.', $fieldName) as $part) {
            if (!is_object($value)) {
                return null;
            }
            $value = $accessor->getValue($value, $part);
        }

        return $value;
    }

    protected function getTotals(ListConfig $brickConfigurator): array
    {
        $crudConfig = $brickConfigurator->getCrudConfig();

        if (count($crudConfig->getTotalFields()) > 0) {
            $dsParams = $brickConfigurator->getDatasourceParams();
            $dsParams->setCount($brickConfigurator->getDatasource()->count($dsParams));
            /** @var array $totalByField */
            $totalByField = $brickConfigurator->getDatasource()->getTotals($dsParams, $crudConfig->getTotalFields());

            return $this->buildTotalFieldViews($crudConfig->getTotalFields(), $totalByField);
        }

        return [];
    }

    protected function getGroupedTotals(ListConfig $brickConfigurator): array
    {
        $crudConfig = $brickConfigurator->getCrudConfig();
        $datasource = $brickConfigurator->getDatasource();

        if (
            empty($crudConfig->getTotalFields())
            || !($datasource instanceof GroupedTotalsInterface)
        ) {
            return [];
        }

        $ruptField1 = null;
        foreach ($brickConfigurator->getFields() as $field) {
            if ($field->getRuptGroup() === 1 && $field->hasSubtotals()) {
                $ruptField1 = $field;
                break;
            }
        }

        if ($ruptField1 === null) {
            return [];
        }

        $dsParams = $brickConfigurator->getDatasourceParams();
        $rawGrouped = $datasource->getGroupedTotals(
            $dsParams,
            $crudConfig->getTotalFields(),
            $ruptField1->getName(),
            $ruptField1->getRuptDateFormat()
        );

        $result = [];
        foreach ($rawGrouped as $groupKey => $rawTotals) {
            $result[(string) $groupKey] = $this->buildTotalFieldViews($crudConfig->getTotalFields(), $rawTotals);
        }

        return $result;
    }

    /** @return FieldView[] */
    private function buildTotalFieldViews(array $totalFields, array $rawTotals): array
    {
        $i = 0;
        $fieldViews = [];
        foreach ($totalFields as $field) {
            $i++;
            $fieldView = new FieldView($field['field'], $rawTotals[$i]);
            $fieldViews[] = $this->fieldRegistry->get($field['field']->getType())->buildView(
                $fieldView,
                $rawTotals[$i]
            );
        }

        return $fieldViews;
    }

    /** @return Field[] */
    protected function getFields(ListConfig $brickConfigurator): array
    {
        return $brickConfigurator->getFields();
    }

    public function getRequestParametersScop(): array
    {
        return ['id'];
    }
}
