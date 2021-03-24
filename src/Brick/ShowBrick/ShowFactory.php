<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\ShowBrick;

use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Contracts\BrickInterface;
use Lle\CruditBundle\Dto\BrickView;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\RessourceView;
use Lle\CruditBundle\Resolver\RessourceResolver;
use Symfony\Component\HttpFoundation\RequestStack;

class ShowFactory implements BrickInterface
{
    /** @var RessourceResolver  */
    protected $ressourceResolver;

    /** @var RequestStack  */
    protected $requestStack;

    public function __construct(
        RessourceResolver $ressourceResolver,
        RequestStack $requestStack
    ) {
        $this->requestStack = $requestStack;
        $this->ressourceResolver = $ressourceResolver;
    }

    public function support(BrickConfigInterface $brickConfigurator): bool
    {
        return (ShowConfig::class === get_class($brickConfigurator));
    }

    public function buildView(BrickConfigInterface $brickConfigurator): BrickView
    {
        $id = $this->requestStack->getMasterRequest()->attributes->get('id');
        $view = new BrickView(spl_object_hash($brickConfigurator));
        if ($brickConfigurator instanceof ShowConfig) {
            $view
                ->setTemplate('@LleCrudit/brick/show_item')
                ->setConfig($brickConfigurator->getConfig())
                ->setData([
                    'item' => $this->getItem($brickConfigurator, $id)
                ]);
        }
        return $view;
    }

    /** @return Field[] */
    private function getFields(ShowConfig $brickConfigurator): array
    {
        return $brickConfigurator->getFields();
    }

    /** @return RessourceView */
    private function getItem(ShowConfig $brickConfigurator, $id): array
    {
        if ($brickConfigurator->getDataSource() !== null) {
            $item = $brickConfigurator->getDataSource()->get($id);
            $data = $this->ressourceResolver->resolve($item, $this->getFields($brickConfigurator));
        }
        return $data->getItem();
    }
}
