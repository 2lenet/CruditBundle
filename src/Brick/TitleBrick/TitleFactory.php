<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\TitleBrick;

use Lle\CruditBundle\Brick\AbstractBasicBrickFactory;
use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Dto\BrickView;

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
                ->setTemplate($brickConfigurator->getTemplate() ?? '@LleCrudit/brick/title')
                ->setConfig($brickConfigurator->getConfig($this->getRequest()))
                ->setData([
                    'entity' => $this->getItem($brickConfigurator),
                ]);
        }

        return $view;
    }

    private function getItem(TitleConfig $brickConfigurator): string
    {
        /** @var \Stringable $resource */
        $resource = $brickConfigurator->getDataSource()->get($this->getRequest()->get('id'));

        return (string)$resource;
    }
}
