<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\ShowBrick;

use Lle\CruditBundle\Brick\AbstractBasicBrickFactory;
use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Dto\BrickView;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\ResourceView;

class ShowFactory extends AbstractBasicBrickFactory
{
    public function support(BrickConfigInterface $brickConfigurator): bool
    {
        return (ShowConfig::class === get_class($brickConfigurator));
    }

    public function buildView(BrickConfigInterface $brickConfigurator): BrickView
    {
        $view = new BrickView($brickConfigurator);
        if ($brickConfigurator instanceof ShowConfig) {
            /** @var object $item */
            $item = $brickConfigurator->getDataSource()->get($this->getRequest()->get('id'));
            $data = ['resource' => $this->getResourceView($brickConfigurator)];
            if (method_exists($item, "getCreatedAt")) {
                $data["createdAt"] = $item->getCreatedAt();
            }
            if (method_exists($item, "getCreatedBy")) {
                $data["createdBy"] = $item->getCreatedBy();
            }
            if (method_exists($item, "getUpdatedAt")) {
                $data["updatedAt"] = $item->getUpdatedAt();
            }
            if (method_exists($item, "getUpdatedBy")) {
                $data["updatedBy"] = $item->getUpdatedBy();
            }
            $view
                ->setTemplate($brickConfigurator->getTemplate() ?? '@LleCrudit/brick/show_item')
                ->setConfig($brickConfigurator->getConfig($this->getRequest()))
                ->setData($data);
        }

        return $view;
    }

    /** @return Field[] */
    private function getFields(ShowConfig $brickConfigurator): array
    {
        return $brickConfigurator->getFields();
    }

    private function getResourceView(ShowConfig $brickConfigurator): ?ResourceView
    {
        $resource = $brickConfigurator->getDataSource()->get($this->getRequest()->get('id'));
        if ($resource) {
            return $this->resourceResolver->resolve(
                $resource,
                $this->getFields($brickConfigurator),
                $brickConfigurator->getDataSource(),
                $brickConfigurator->getCrudConfig()
            );
        }

        return null;
    }
}
