<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\TemplateBrick;

use Lle\CruditBundle\Brick\AbstractBasicBrickFactory;
use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Dto\BrickView;
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
            ->setTemplate($brickConfigurator->getTemplate() ?? '@LleCrudit/brick/template')
            ->setConfig($brickConfigurator->getConfig($this->getRequest()))
            ->setData([
                'resource' => $this->getResourceView($brickConfigurator),
                'title' => $brickConfigurator->getTitle(),
                'titleCss' => $brickConfigurator->getTitleCss(),
            ]);

        return $view;
    }

    private function getResourceView(TemplateConfig $brickConfigurator): ?ResourceView
    {
        if ($this->getRequest()->query->has('id') || $this->getRequest()->attributes->has('id')) {
            $resource = $brickConfigurator->getDataSource()->get($this->getRequest()->attributes->get('id'));
            if ($resource) {
                return $this->resourceResolver->resolve(
                    $resource,
                    [],
                    $brickConfigurator->getDataSource()
                );
            }
        }

        return null;
    }
}
