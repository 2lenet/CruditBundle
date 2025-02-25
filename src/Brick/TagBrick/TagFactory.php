<?php

namespace Lle\CruditBundle\Brick\TagBrick;

use Lle\CruditBundle\Brick\AbstractBasicBrickFactory;
use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Dto\BrickView;

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
            $resource = $brickConfigurator->getDatasource()->get($objectId);

            $data = $brickConfigurator->getTagsMethod()
                ? $brickConfigurator->getDatasource()->{$brickConfigurator->getTagsMethod()}($resource)
                : $brickConfigurator->getDatasource()->getTags($resource);

            $view
                ->setTemplate($brickConfigurator->getTemplate() ?? '@LleCrudit/brick/tag')
                ->setConfig($config)
                ->setData([
                    'tags' => $data['tags'],
                    'currentTags' => $data['currentTags'],
                    'editRoute' => $brickConfigurator->getEditRoute(),
                    'editRole' => $brickConfigurator->getEditRole(),
                ]);
        }

        return $view;
    }
}
