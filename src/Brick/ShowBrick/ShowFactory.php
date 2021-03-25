<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\ShowBrick;

use Lle\CruditBundle\Brick\AbstractBasicBrickFactory;
use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Dto\BrickView;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\RessourceView;

class ShowFactory extends AbstractBasicBrickFactory
{
    public function support(BrickConfigInterface $brickConfigurator): bool
    {
        return (ShowConfig::class === get_class($brickConfigurator));
    }

    public function buildView(BrickConfigInterface $brickConfigurator): BrickView
    {

        $view = new BrickView(spl_object_hash($brickConfigurator));
        if ($brickConfigurator instanceof ShowConfig) {
            $view
                ->setTemplate('@LleCrudit/brick/show_item')
                ->setConfig($brickConfigurator->getConfig())
                ->setData([
                    'item' => $this->getItem($brickConfigurator, $this->getRequest()->attributes->get('id'))
                ]);
        }
        return $view;
    }

    /** @return Field[] */
    private function getFields(ShowConfig $brickConfigurator): array
    {
        return $brickConfigurator->getFields();
    }

    private function getItem(ShowConfig $brickConfigurator, string $id): ?RessourceView
    {
        $item = $brickConfigurator->getDataSource()->get($id);
        if ($item) {
            return $this->ressourceResolver->resolve($item, $this->getFields($brickConfigurator));
        }
        return null;
    }
}
