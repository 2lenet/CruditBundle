<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\ShowBrick;

use Lle\CruditBundle\Brick\AbstractBasicBrickFactory;
use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Dto\BrickView;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\ResourceView;

class ShowFactory extends AbstractBasicBrickFactory
{
    public function support(BrickConfigInterface $brickConfigurator): bool
    {
        return (ShowConfig::class === get_class($brickConfigurator));
    }

    public function buildView(BrickConfigInterface $brickConfigurator): BrickView
    {

        $view = new BrickView($brickConfigurator);
        if ($brickConfigurator instanceof ShowConfig) {
            $view
                ->setTemplate('@LleCrudit/brick/show_item')
                ->setConfig($brickConfigurator->getConfig())
                ->setData([
                    'resource' => $this->getResourceView($brickConfigurator)
                ]);
        }
        return $view;
    }

    /** @return Field[] */
    private function getFields(ShowConfig $brickConfigurator): array
    {
        return $brickConfigurator->getFields();
    }

    private function getResourceView(ShowConfig $brickConfigurator): ?ResourceView
    {
        $resource = $brickConfigurator->getDataSource()->get($this->getRequest()->get('id'));
        if ($resource) {
            return $this->resourceResolver->resolve(
                $resource,
                $this->getFields($brickConfigurator),
                $brickConfigurator->getDataSource()
            );
        }
        return null;
    }
}
