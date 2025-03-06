<?php

namespace Lle\CruditBundle\Brick\TagBrick;

use Lle\CruditBundle\Brick\AbstractBasicBrickFactory;
use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Dto\BrickView;
use Lle\CruditBundle\Exception\CruditException;

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
            if (!$resource) {
                throw new CruditException(
                    sprintf(
                        "Resource %s of class %s not found",
                        $this->getRequest()->get("id", "NO_ID"),
                        $brickConfigurator->getDatasource()->getClassName()
                    )
                );
            }

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
