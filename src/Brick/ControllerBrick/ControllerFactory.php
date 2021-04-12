<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\ControllerBrick;

use Lle\CruditBundle\Brick\AbstractBasicBrickFactory;
use Lle\CruditBundle\Dto\BrickView;
use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Dto\ResourceView;

class ControllerFactory extends AbstractBasicBrickFactory
{

    public function support(BrickConfigInterface $brickConfigurator): bool
    {
        return (ControllerConfig::class === get_class($brickConfigurator));
    }

    public function buildView(BrickConfigInterface $brickConfigurator): BrickView
    {
        /** @var ControllerConfig $brickConfigurator */
        $view = new BrickView(spl_object_hash($brickConfigurator));
        $view
            ->setTemplate('@LleCrudit/brick/controller')
            ->setConfig($brickConfigurator->getConfig())
            ->setData(['resource' => $this->getResourceView($brickConfigurator)]);
        return $view;
    }

    private function getResourceView(ControllerConfig $brickConfigurator): ?ResourceView
    {
        $item = $brickConfigurator->getDataSource()->get($this->getRequest()->get('id'));
        if ($item) {
            return $this->resourceResolver->resolve(
                $item,
                [],
                $brickConfigurator->getDataSource()
            );
        }
        return null;
    }
}
