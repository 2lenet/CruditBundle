<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\LinksBrick;

use Lle\CruditBundle\Brick\AbstractBasicBrickFactory;
use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Dto\Action\ListAction;
use Lle\CruditBundle\Dto\BrickView;
use Lle\CruditBundle\Resolver\ResourceResolver;
use Lle\CruditBundle\Service\NavigationStack;
use Symfony\Component\HttpFoundation\RequestStack;

class LinksFactory extends AbstractBasicBrickFactory
{
    public function __construct(
        ResourceResolver $resourceResolver,
        RequestStack $requestStack,
        protected NavigationStack $navigationStack,
    ) {
        parent::__construct($resourceResolver, $requestStack);
    }

    public function support(BrickConfigInterface $brickConfigurator): bool
    {
        return (LinksConfig::class === get_class($brickConfigurator));
    }

    public function buildView(BrickConfigInterface $brickConfigurator): BrickView
    {
        /** @var LinksConfig $brickConfigurator */
        /** @var \Stringable $resource */
        $resource = $this->getItem($brickConfigurator);

        /** @var LinksConfig $brickConfigurator */
        $view = new BrickView($brickConfigurator);
        $view
            ->setTemplate($brickConfigurator->getTemplate() ?? '@LleCrudit/brick/links')
            ->setConfig($brickConfigurator->getConfig($this->getRequest()))
            ->setData([
                'actions' => $this->getActions($brickConfigurator),
                'entity' => (string)$resource,
                'resource' => $resource,
            ]);

        return $view;
    }

    public function getActions(LinksConfig $brickConfig): array
    {
        $backUrl = $this->navigationStack->peek();
        $actions = $brickConfig->getActions();

        // Resolve the back URL for "back to list" actions (covers ListAction on list pages and ItemAction on show pages)
        foreach ($actions as $action) {
            if (
                $action->getLabel() === CrudConfigInterface::LABEL_ACTION_LIST
                && $action->getUrl() === null
                && $backUrl !== null
            ) {
                $action->setUrl($backUrl);
            }
        }

        if ($brickConfig->hasBack()) {
            $action = ListAction::new('crudit.action.back', $brickConfig->getCrudConfig()->getPath());
            if ($backUrl !== null) {
                $action->setUrl($backUrl);
            }
            $actions[] = $action;
        }

        return $actions;
    }

    private function getItem(LinksConfig $brickConfigurator): ?object
    {
        $id = $this->getRequest()->attributes->get('id');
        if ($id) {
            $resource = $brickConfigurator->getDataSource()->get($id);

            return $resource;
        } else {
            return null;
        }
    }
}
