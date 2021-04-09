<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\ListBrick;

use Lle\CruditBundle\Brick\AbstractBasicBrickFactory;
use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Dto\BrickView;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\ResourceView;
use Lle\CruditBundle\Registry\DatasourceRegistry;
use Lle\CruditBundle\Resolver\ResourceResolver;
use Symfony\Component\HttpFoundation\RequestStack;

class ListFactory extends AbstractBasicBrickFactory
{
    /** @var DatasourceRegistry */
    private $datasourceRegistry;

    public function __construct(
        ResourceResolver $resourceResolver,
        RequestStack $requestStack,
        DatasourceRegistry $datasourceRegistry
    ) {
        $this->datasourceRegistry = $datasourceRegistry;
        parent::__construct($resourceResolver, $requestStack);
    }

    public function support(BrickConfigInterface $brickConfigurator): bool
    {
        return (ListConfig::class === get_class($brickConfigurator));
    }

    public function buildView(BrickConfigInterface $brickConfigurator): BrickView
    {
        $view = new BrickView($brickConfigurator->getId());
        if ($brickConfigurator instanceof ListConfig) {
            $view
                ->setPath(
                    $brickConfigurator->getCrudConfig()->getPath('brickapi', [
                        'id' => $brickConfigurator->getId(),
                        'pageKey' => $brickConfigurator->getPageKey()
                    ])
                )
                ->setTemplate('@LleCrudit/brick/list_items')
                ->setConfig($brickConfigurator->getConfig())
                ->setData([
                    'lines' => $this->getLines($brickConfigurator)
                ]);
        }
        return $view;
    }

    /** @return Field[] */
    private function getFields(ListConfig $brickConfigurator): array
    {
        return $brickConfigurator->getFields();
    }

    /** @return ResourceView[] */
    private function getLines(ListConfig $brickConfigurator): array
    {
        $lines = [];
        if ($brickConfigurator->getDataSource() !== null) {
            if ($this->getRequest()->get('id')) {
                $datasource = $brickConfigurator->getClassName() !== null ?
                    $this->datasourceRegistry->getByClass($brickConfigurator->getClassName()) :
                    $brickConfigurator->getDatasource();
                $resource = $brickConfigurator->getDataSource()->get($this->getRequest()->get('id'));
                $fieldName = $brickConfigurator->getFieldNameAssociation() ??
                    $datasource->getAssociationFieldName($brickConfigurator->getDataSource()->getClassName());
                $query = $datasource->createQuery('assoc');
                if ($brickConfigurator->hasCatchQueryAssociation()) {
                    $query = $brickConfigurator->catchQueryAssociation($query, 'assoc');
                } elseif ($fieldName !== null) {
                    $query
                        ->where('assoc. ' . $fieldName . ' = :id')
                        ->setParameter('id', $this->getRequest()->get('id'));
                }
                foreach ($query->execute() as $item) {
                    $lines[] = $this->resourceResolver->resolve(
                        $item,
                        $this->getFields($brickConfigurator),
                        $datasource
                    );
                }
            } else {
                foreach ($brickConfigurator->getDataSource()->list() as $item) {
                    $lines[] = $this->resourceResolver->resolve(
                        $item,
                        $this->getFields($brickConfigurator),
                        $brickConfigurator->getDataSource()
                    );
                }
            }
        }
        return $lines;
    }
}
