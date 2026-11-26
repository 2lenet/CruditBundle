<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\TabBrick;

use Lle\CruditBundle\Brick\AbstractBasicBrickFactory;
use Lle\CruditBundle\Brick\FormBrick\FormConfig;
use Lle\CruditBundle\Builder\BrickBuilder;
use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Dto\BrickView;
use Lle\CruditBundle\Resolver\ResourceResolver;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * The tabs of a record: only the tabs granted (their role, checked here: the bricks of a tab not granted are not built,
 * its form is not bound) and shown (displayIf, displayIfByRequest), each with its counter computed on the record. A form of a tab knows
 * its tab, to come back to it once saved.
 */
class TabFactory extends AbstractBasicBrickFactory
{
    private BrickBuilder $brickBuilder;

    public function __construct(
        ResourceResolver $resourceResolver,
        RequestStack $requestStack,
        BrickBuilder $brickBuilder,
        private AuthorizationCheckerInterface $authorizationChecker,
    ) {
        $this->brickBuilder = $brickBuilder;
        parent::__construct($resourceResolver, $requestStack);
    }

    public function support(BrickConfigInterface $brickConfigurator): bool
    {
        return (TabConfig::class === get_class($brickConfigurator));
    }

    public function buildView(BrickConfigInterface $brickConfigurator): BrickView
    {
        $tabs = [];
        if ($brickConfigurator instanceof TabConfig) {
            $resource = $this->getResource($brickConfigurator);
            foreach ($brickConfigurator->getTabs() as $tab) {
                $role = $tab->getRole();
                if (
                    (null !== $role && !$this->authorizationChecker->isGranted($role))
                    || !$tab->isDisplayed($resource)
                    || !$tab->isDisplayedByRequest($this->getRequest())
                ) {
                    continue;
                }
                $id = (string) $tab->getId();
                $view = (new TabView($tab->getLabel(), $id))
                    ->setRole($role)
                    ->setCount($tab->getCount($resource));
                foreach ($tab->getBricks() as $brickConfig) {
                    // The config is changed: it is built again for each request (getTabs() of the CrudConfig)
                    if ($brickConfig instanceof FormConfig) {
                        $brickConfig->setTab($id);
                    }
                    $view->add(
                        $this->brickBuilder->buildBrick(
                            $brickConfigurator->getCrudConfig(),
                            $brickConfigurator->getPageKey(),
                            $brickConfig
                        )
                    );
                }
                $tabs[] = $view;
            }
        }
        $view = new BrickView($brickConfigurator);
        $view
            ->setTemplate($brickConfigurator->getTemplate() ?? '@LleCrudit/brick/tab')
            ->setConfig(
                ['tabs' => $tabs, 'translation_domain' => $brickConfigurator->getCrudConfig()->getTranslationDomain()]
            )
            ->setData([]);

        return $view;
    }

    private function getResource(TabConfig $brickConfigurator): ?object
    {
        $datasource = $brickConfigurator->getCrudConfig()->getDatasource();

        $resource = null;
        if ($this->getRequest()->attributes->get('id')) {
            $resource = $datasource->get($this->getRequest()->attributes->get('id'));
        }

        return $resource;
    }
}
