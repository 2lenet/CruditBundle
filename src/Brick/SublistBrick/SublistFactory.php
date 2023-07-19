<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\SublistBrick;

use Lle\CruditBundle\Brick\AbstractBasicBrickFactory;
use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Datasource\DatasourceFilter;
use Lle\CruditBundle\Dto\BrickView;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\Path;
use Lle\CruditBundle\Dto\ResourceView;
use Lle\CruditBundle\Resolver\ResourceResolver;
use Symfony\Component\HttpFoundation\RequestStack;

class SublistFactory extends AbstractBasicBrickFactory
{
    public function __construct(
        ResourceResolver $resourceResolver,
        RequestStack $requestStack
    ) {
        parent::__construct($resourceResolver, $requestStack);
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
                ->setTemplate('@LleCrudit/brick/sublist_items')
                ->setConfig($config)
                ->setPath($this->getPath($brickConfigurator))
                ->setData([
                    'lines' => $this->getLines($brickConfigurator),
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
        $foreignKeyValue = $this->getRequest()->get('id');
        // normal list
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
