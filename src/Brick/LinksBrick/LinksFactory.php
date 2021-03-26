<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\LinksBrick;

use Lle\CruditBundle\Brick\AbstractBasicBrickFactory;
use Lle\CruditBundle\Brick\AbstractBrickConfig;
use Lle\CruditBundle\Brick\ListBrick\ListConfig;
use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Contracts\BrickInterface;
use Lle\CruditBundle\Dto\Action\BackAction;
use Lle\CruditBundle\Dto\BrickView;

class LinksFactory extends AbstractBasicBrickFactory
{
    public function support(BrickConfigInterface $brickConfigurator): bool
    {
        return (LinksConfig::class === get_class($brickConfigurator));
    }

    public function buildView(BrickConfigInterface $brickConfigurator): BrickView
    {
        /** @var LinksConfig $brickConfigurator */
        $view = new BrickView(spl_object_hash($brickConfigurator));
        $view
            ->setTemplate('@LleCrudit/brick/links')
            ->setConfig($brickConfigurator->getConfig())
            ->setData([
                'actions' => $this->getActions($brickConfigurator)
            ]);
        return $view;
    }

    //@TODO ListAction -> ActionResolver -> ActionView
    public function getActions(LinksConfig $brickConfig): array
    {
        $actions = [];
        foreach ($brickConfig->getActions() as $action)
        {
            dump($action);
            if($action instanceof BackAction) {
                $action->generate($brickConfig->getCrudConfig(), $this->getRequest());
            }
            $actions[] = $action;
        }
        return $actions;
    }
}
