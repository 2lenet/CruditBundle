<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\TemplateBrick;

use Lle\CruditBundle\Brick\AbstractBasicBrickFactory;
use Lle\CruditBundle\Dto\BrickView;
use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Dto\ResourceView;

class TemplateFactory extends AbstractBasicBrickFactory
{

    public function support(BrickConfigInterface $brickConfigurator): bool
    {
        return (TemplateConfig::class === get_class($brickConfigurator));
    }

    public function buildView(BrickConfigInterface $brickConfigurator): BrickView
    {
        /** @var TemplateConfig $brickConfigurator */
        $view = new BrickView($brickConfigurator);
        $view
            ->setTemplate('@LleCrudit/brick/template')
            ->setConfig($brickConfigurator->getConfig())
            ->setData(['resource' => $this->getResourceView($brickConfigurator)]);
        return $view;
    }

    private function getResourceView(TemplateConfig $brickConfigurator): ?ResourceView
    {
        $resource = $brickConfigurator->getDataSource()->get($this->getRequest()->get('id'));
        if ($resource) {
            return $this->resourceResolver->resolve(
                $resource,
                [],
                $brickConfigurator->getDataSource()
            );
        }
        return null;
    }
}
