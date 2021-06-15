<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\MapBrick;

use Lle\CruditBundle\Brick\AbstractBasicBrickFactory;
use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Dto\BrickView;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\ResourceView;

class MapFactory extends AbstractBasicBrickFactory
{
    public function support(BrickConfigInterface $brickConfigurator): bool
    {
        return (MapConfig::class === get_class($brickConfigurator));
    }

    public function buildView(BrickConfigInterface $brickConfigurator): BrickView
    {

        $view = new BrickView($brickConfigurator);
        if ($brickConfigurator instanceof MapConfig) {
            $view
                ->setTemplate('@LleCrudit/brick/map')
                ->setConfig($brickConfigurator->getConfig($this->getRequest()))
                ->setData([
                    'resource' => $this->getResourceView($brickConfigurator)
                ]);
        }
        return $view;
    }

    /** @return Field[] */
    private function getFields(MapConfig $brickConfigurator): array
    {
        return $brickConfigurator->getFields();
    }

    private function getResourceView(MapConfig $brickConfigurator): ?ResourceView
    {
        if ($this->getRequest()->get('id')) {
            $resource = $brickConfigurator->getDataSource()->get($this->getRequest()->get('id'));
            if ($resource) {
                return $this->resourceResolver->resolve(
                    $resource,
                    $this->getFields($brickConfigurator),
                    $brickConfigurator->getDataSource()
                );
            }
        }
        return null;
    }
}
