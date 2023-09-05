<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Builder;

use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Dto\BrickView;
use Lle\CruditBundle\Exception\UnsupportedBrickConfigurationException;
use Lle\CruditBundle\Provider\BrickProvider;
use Symfony\Component\HttpFoundation\Request;

class BrickBuilder
{
    /** @var BrickView[] */
    private array $bricks = [];
    private BrickProvider $brickProvider;

    public function __construct(
        BrickProvider $brickProvider,
    ) {
        $this->brickProvider = $brickProvider;
    }

    private function init(
        CrudConfigInterface $crudConfig,
        string $pageKey,
        int $position,
        BrickConfigInterface $brickConfig,
    ): void {
        foreach (array_merge([$brickConfig], $brickConfig->getChildren()) as $k => $brick) {
            $brick->setCrudConfig($crudConfig);
            $brick->setPageKey($pageKey);
            $brick->setId(md5(get_class($crudConfig) . '-' . $pageKey . '-' . $position . '-' . $k));
        }
    }

    /** @return BrickView[] */
    public function build(CrudConfigInterface $crudConfig, string $pageKey): array
    {
        $bricks = $crudConfig->getBrickConfigs();
        foreach ($bricks[$pageKey] as $k => $brickConfig) {
            $this->init($crudConfig, $pageKey, $k, $brickConfig);
            $this->bricks[] = $this->buildBrick($crudConfig, $pageKey, $brickConfig);
        }

        return $this->bricks;
    }

    public function buildBrick(
        CrudConfigInterface $crudConfig,
        string $pageKey,
        BrickConfigInterface $brickConfig,
    ): BrickView {
        $brickFactory = $this->brickProvider->getBrick($brickConfig);
        if ($brickFactory) {
            return $brickFactory->buildView($brickConfig);
        } else {
            throw new UnsupportedBrickConfigurationException(get_class($brickConfig));
        }
    }

    public function getView(CrudConfigInterface $crudConfig, string $id): ?BrickView
    {
        foreach ($crudConfig->getBrickConfigs() as $pageKey => $page) {
            foreach ($page as $k => $brickConfig) {
                $this->init($crudConfig, $pageKey, $k, $brickConfig);
                if ($brickConfig->getId() === $id) {
                    return $this->buildBrick($crudConfig, $pageKey, $brickConfig);
                }
                foreach ($brickConfig->getChildren() as $brickChild) {
                    if ($brickChild->getId() === $id) {
                        return $this->buildBrick($crudConfig, $pageKey, $brickChild);
                    }
                }
            }
        }

        return null;
    }

    public function bindRequest(Request $request): void
    {
    }
}
