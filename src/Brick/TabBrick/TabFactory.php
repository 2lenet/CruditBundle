<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\TabBrick;

use Lle\CruditBundle\Brick\AbstractBasicBrickFactory;
use Lle\CruditBundle\Builder\BrickBuilder;
use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Dto\BrickView;
use Lle\CruditBundle\Resolver\ResourceResolver;
use Symfony\Component\HttpFoundation\RequestStack;

class TabFactory extends AbstractBasicBrickFactory
{
    /** @var BrickBuilder  */
    private $brickBuilder;
    public function __construct(
        ResourceResolver $resourceResolver,
        RequestStack $requestStack,
        BrickBuilder $brickBuilder
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
            foreach ($brickConfigurator->getTabs() as $k => $tab) {
                $tabs[$k] = new TabView($tab->getLabel());
                foreach ($tab->getBricks() as $brickConfig) {
                    $tabs[$k]->add(
                        $this->brickBuilder->buildBrick(
                            $brickConfigurator->getCrudConfig(),
                            $brickConfigurator->getPageKey(),
                            $brickConfig
                        )
                    );
                }
            }
        }
        $view = new BrickView($brickConfigurator);
        $view
            ->setTemplate('@LleCrudit/brick/tab')
            ->setConfig(['tabs' => $tabs])
            ->setData([]);
        return $view;
    }
}
