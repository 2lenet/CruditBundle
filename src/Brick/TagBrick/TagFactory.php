<?php

namespace Lle\CruditBundle\Brick\TagBrick;

use Lle\CruditBundle\Brick\AbstractBasicBrickFactory;
use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Datasource\DatasourceFilter;
use Lle\CruditBundle\Datasource\DatasourceParams;
use Lle\CruditBundle\Dto\BrickView;
use Symfony\Component\VarDumper\Cloner\Data;

class TagFactory extends AbstractBasicBrickFactory
{
    public function support(BrickConfigInterface $brickConfigurator): bool
    {
        return (TagConfig::class === get_class($brickConfigurator));
    }

    public function buildView(BrickConfigInterface $brickConfigurator): BrickView
    {
        $view = new BrickView($brickConfigurator);
        if ($brickConfigurator instanceof TagConfig) {
            $config = $brickConfigurator->getConfig($this->getRequest());
            $objectId = $this->getRequest()->get('id');

            $data = $brickConfigurator->getTagsMethod()
                ? $brickConfigurator->getDatasource()->{$brickConfigurator->getTagsMethod()}($objectId)
                : $brickConfigurator->getDatasource()->getTags($objectId);

            $view
                ->setTemplate($brickConfigurator->getTemplate() ?? '@LleCrudit/brick/tag')
                ->setConfig($config)
                ->setData([
                    'tags' => $data['tags'],
                    'currentTags' => $data['currentTags'],
                    'editRoute' => $brickConfigurator->getEditRoute(),
                ]);
        }

        return $view;
    }
}
