<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Builder;

use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Dto\BrickView;
use Lle\CruditBundle\Exception\UnsupportedBrickConfigurationException;
use Symfony\Component\HttpFoundation\Request;
use Lle\CruditBundle\Provider\BrickProvider;

class BrickBuilder
{

    /** @var BrickView[]  */
    private $bricks = [];

    /** @var BrickProvider  */
    private $brickProvider;

    public function __construct(
        BrickProvider $brickProvider
    ) {
        $this->brickProvider = $brickProvider;
    }

    /** @return BrickView[] */
    public function build(CrudConfigInterface $crudConfig, string $pageKey, Request $request): array
    {
        foreach ($crudConfig->getBrickConfigs($request, $pageKey) as $brickConfig) {
            $this->bricks[] = $this->buildBrick($crudConfig, $pageKey, $brickConfig);
        }
        return $this->bricks;
    }

    public function buildBrick(
        CrudConfigInterface $crudConfig,
        string $pageKey,
        BrickConfigInterface $brickConfig
    ): BrickView {
        $brickConfig->setCrudConfig($crudConfig);
        $brickConfig->setPageKey($pageKey);
        $brickFactory = $this->brickProvider->getBrick($brickConfig);
        if ($brickFactory) {
            return $brickFactory->buildView($brickConfig);
        } else {
            throw new UnsupportedBrickConfigurationException(get_class($brickConfig));
        }
    }

    public function getView(CrudConfigInterface $crudConfig, string $pageKey, Request $request, string $id): ?BrickView
    {
        foreach ($crudConfig->getBrickConfigs($request, $pageKey) as $brickConfig) {
            $brickConfig->setCrudConfig($crudConfig);
            $brickConfig->setPageKey($pageKey);
            if ($brickConfig->getId() === $id) {
                return $this->buildBrick($crudConfig, $pageKey, $brickConfig);
            }
        }
        return null;
    }

    public function bindRequest(Request $request): void
    {
    }
}
