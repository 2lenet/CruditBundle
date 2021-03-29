<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\LinksBrick;

use Lle\CruditBundle\Brick\AbstractBasicBrickFactory;
use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Dto\Action\ListAction;
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

    public function getActions(LinksConfig $brickConfig): array
    {
        $actions = $brickConfig->getActions();
        if ($brickConfig->hasBack()) {
            $action = ListAction::new('crudit.action.back', $brickConfig->getCrudConfig()->getPath());
            $url = $this->getRequest()->headers->get('referer');
            if ($url !== null) {
                $action->setUrl($url);
            }
            $actions[] = $action;
        }
        return $actions;
    }
}
