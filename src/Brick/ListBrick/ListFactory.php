<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\ListBrick;

use Lle\CruditBundle\Brick\AbstractBasicBrickFactory;
use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Dto\BrickView;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\RessourceView;

class ListFactory extends AbstractBasicBrickFactory
{
    public function support(BrickConfigInterface $brickConfigurator): bool
    {
        return (ListConfig::class === get_class($brickConfigurator));
    }

    public function buildView(BrickConfigInterface $brickConfigurator): BrickView
    {
        $view = new BrickView($brickConfigurator->getId());
        if ($brickConfigurator instanceof ListConfig) {
            $view
                ->setPath(
                    $brickConfigurator->getCrudConfig()->getPath('brickapi', [
                        'id' => $brickConfigurator->getId(),
                        'pageKey' => $brickConfigurator->getPageKey()
                    ])
                )
                ->setTemplate('@LleCrudit/brick/list_items')
                ->setConfig($brickConfigurator->getConfig())
                ->setData([
                    'lines' => $this->getLines($brickConfigurator)
                ]);
        }
        return $view;
    }

    /** @return Field[] */
    private function getFields(ListConfig $brickConfigurator): array
    {
        return $brickConfigurator->getFields();
    }

    /** @return RessourceView[] */
    private function getLines(ListConfig $brickConfigurator): array
    {
        $lines = [];
        if ($brickConfigurator->getDataSource() !== null) {
            foreach ($brickConfigurator->getDataSource()->list() as $item) {
                $lines[] = $this->ressourceResolver->resolve(
                    $item,
                    $this->getFields($brickConfigurator),
                    $brickConfigurator->getDataSource()
                );
            }
        }
        return $lines;
    }
}
