<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\SublistBrick;

use Lle\CruditBundle\Brick\AbstractBasicBrickFactory;
use Lle\CruditBundle\Brick\RuptureAwareTrait;
use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Datasource\DatasourceFilter;
use Lle\CruditBundle\Datasource\DatasourceParams;
use Lle\CruditBundle\Dto\BrickView;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\Path;
use Lle\CruditBundle\Dto\ResourceView;
use Lle\CruditBundle\Exception\CruditException;
use Lle\CruditBundle\Registry\FieldRegistry;
use Lle\CruditBundle\Resolver\ResourceResolver;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class SublistFactory extends AbstractBasicBrickFactory
{
    use RuptureAwareTrait;

    protected FieldRegistry $fieldRegistry;
    protected TranslatorInterface $translator;

    public function __construct(
        ResourceResolver $resourceResolver,
        RequestStack $requestStack,
        FieldRegistry $fieldRegistry,
        TranslatorInterface $translator,
    ) {
        parent::__construct($resourceResolver, $requestStack);
        $this->fieldRegistry = $fieldRegistry;
        $this->translator = $translator;
    }

    public function support(BrickConfigInterface $brickConfigurator): bool
    {
        return (SublistConfig::class === get_class($brickConfigurator));
    }

    public function buildView(BrickConfigInterface $brickConfigurator): BrickView
    {
        $view = new BrickView($brickConfigurator);
        if ($brickConfigurator instanceof SublistConfig) {
            $config = $brickConfigurator->getConfig($this->getRequest());

            $view
                ->setTemplate($brickConfigurator->getTemplate() ?? '@LleCrudit/brick/sublist_items')
                ->setConfig($config)
                ->setPath($this->getPath($brickConfigurator))
                ->setData([
                    'resource' => $this->getResource($brickConfigurator),
                    'lines' => $this->getLines($brickConfigurator),
                    'totals' => $this->getTotals($brickConfigurator),
                    'grouped_totals' => $this->getGroupedTotals($brickConfigurator),
                    'batch_actions' => [], // to use the same pager template as list
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
    private function getLines(SublistConfig $brickConfigurator): array
    {
        $lines = [];
        $foreignKeyValue = $this->getRequest()->attributes->get('id');
        // normal list
        /** @var DatasourceParams $dsParams */
        $dsParams = $brickConfigurator->getDatasourceParams();
        $fkFilter = new DatasourceFilter($brickConfigurator->getFieldname(), $foreignKeyValue);
        $fkFilter->setOperator("IN");
        $dsParams->setEnableFilters(false);
        $dsParams->setFilters(array_merge($dsParams->getFilters(), [$fkFilter]));
        $dsParams->setCount($brickConfigurator->getDatasource()->count($dsParams));
        $this->prependRuptureSorts($dsParams, $brickConfigurator->getFields());
        $resources = $brickConfigurator->getDatasource()->list($dsParams);

        foreach ($resources as $resource) {
            $lines[] = $this->resourceResolver->resolve(
                $resource,
                $this->getFields($brickConfigurator),
                $brickConfigurator->getDatasource(),
                $brickConfigurator->getSubCrudConfig()
            );
        }

        return $this->annotateRuptureBreaks($lines);
    }

    private function getResource(SublistConfig $brickConfigurator): object
    {
        $datasource = $brickConfigurator->getCrudConfig()->getDatasource();

        $resource = null;
        if ($this->getRequest()->attributes->get('id')) {
            $resource = $datasource->get($this->getRequest()->attributes->get('id'));
        } else {
            $resource = $datasource->newInstance();
        }

        if ($resource === null) {
            throw new CruditException('Resource not found');
        }

        return $resource;
    }

    private function getTotals(SublistConfig $brickConfigurator): array
    {
        /** @var CrudConfigInterface $crudConfig */
        $crudConfig = $brickConfigurator->getSubCrudConfig();

        if (count($crudConfig->getSublistTotalFields()) > 0) {
            /** @var DatasourceParams $dsParams */
            $dsParams = $brickConfigurator->getDatasourceParams();
            $dsParams->setCount($brickConfigurator->getDatasource()->count($dsParams));
            /** @var array $totalByField */
            $totalByField = $brickConfigurator->getDatasource()->getTotals($dsParams, $crudConfig->getSublistTotalFields());

            return $this->buildTotalFieldViews($crudConfig->getSublistTotalFields(), $totalByField);
        }

        return [];
    }

    private function getGroupedTotals(SublistConfig $brickConfigurator): array
    {
        /** @var CrudConfigInterface $crudConfig */
        $crudConfig = $brickConfigurator->getSubCrudConfig();

        return $this->computeGroupedTotals(
            $brickConfigurator->getDatasource(),
            $brickConfigurator->getDatasourceParams(),
            $crudConfig->getSublistTotalFields(),
            $brickConfigurator->getFields()
        );
    }

    /** @return Field[] */
    private function getFields(SublistConfig $brickConfigurator): array
    {
        return $brickConfigurator->getFields();
    }

    public function getRequestParametersScop(): array
    {
        return ['id'];
    }
}
