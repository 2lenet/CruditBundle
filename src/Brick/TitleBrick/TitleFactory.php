<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\TitleBrick;

use Lle\CruditBundle\Brick\AbstractBasicBrickFactory;
use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Dto\BrickView;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\ResourceView;

class TitleFactory extends AbstractBasicBrickFactory
{
    public function support(BrickConfigInterface $brickConfigurator): bool
    {
        return (TitleConfig::class === get_class($brickConfigurator));
    }

    public function buildView(BrickConfigInterface $brickConfigurator): BrickView
    {
        $view = new BrickView($brickConfigurator);
        if ($brickConfigurator instanceof TitleConfig) {
            $view
                ->setTemplate('@LleCrudit/brick/title')
                ->setConfig($brickConfigurator->getConfig($this->getRequest()))
                ->setData([
                    'entity' => $this->getItem($brickConfigurator)
                ]);
        }
        return $view;
    }
    
    private function getItem(TitleConfig $brickConfigurator): ?string
    {
        $resource = $brickConfigurator->getDataSource()->get($this->getRequest()->get('id'));
        return (string)$resource;
    }
}
