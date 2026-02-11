<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\SublistBrick;

use Lle\CruditBundle\Brick\AbstractBasicBrickFactory;
use Lle\CruditBundle\Brick\ListBrick\ListConfig;
use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Datasource\DatasourceFilter;
use Lle\CruditBundle\Datasource\DatasourceParams;
use Lle\CruditBundle\Dto\BrickView;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\FieldView;
use Lle\CruditBundle\Dto\Path;
use Lle\CruditBundle\Dto\ResourceView;
use Lle\CruditBundle\Exception\CruditException;
use Lle\CruditBundle\Registry\FieldRegistry;
use Lle\CruditBundle\Resolver\ResourceResolver;
use Symfony\Component\HttpFoundation\RequestStack;

class SublistFactory extends AbstractBasicBrickFactory
{
    protected FieldRegistry $fieldRegistry;

    public function __construct(
        ResourceResolver $resourceResolver,
        RequestStack $requestStack,
        FieldRegistry $fieldRegistry,
    ) {
        parent::__construct($resourceResolver, $requestStack);
        $this->fieldRegistry = $fieldRegistry;
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
        $resources = $brickConfigurator->getDatasource()->list($dsParams);

        foreach ($resources as $resource) {
            $lines[] = $this->resourceResolver->resolve(
                $resource,
                $this->getFields($brickConfigurator),
                $brickConfigurator->getDatasource(),
                $brickConfigurator->getSubCrudConfig()
            );
        }

        return $lines;
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
        $crudConfig = $brickConfigurator->getSubCrudConfig();

        if (count($crudConfig->getSublistTotalFields()) > 0) {
            $dsParams = $brickConfigurator->getDatasourceParams();
            $dsParams->setCount($brickConfigurator->getDatasource()->count($dsParams));
            /** @var array $totalByField */
            $totalByField = $brickConfigurator->getDatasource()->getTotals($dsParams, $crudConfig->getSublistTotalFields());

            $i = 0;
            $fieldViews = [];
            foreach ($crudConfig->getSublistTotalFields() as $field) {
                $i++;
                $fieldView = new FieldView($field['field'], $totalByField[$i]);
                $fieldViews[] = $this->fieldRegistry->get($field['field']->getType())->buildView(
                    $fieldView,
                    $totalByField[$i]
                );
            }

            return $fieldViews;
        }

        return [];
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
