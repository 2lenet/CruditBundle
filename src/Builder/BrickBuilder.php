<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Builder;

use Lle\CruditBundle\Contracts\PageConfigInterface;
use Lle\CruditBundle\Dto\BrickView;
use Lle\CruditBundle\Exception\UnsupportedBrickConfigurationException;
use Symfony\Component\HttpFoundation\Request;
use Lle\CruditBundle\Provider\BrickProvider;
use Lle\CruditBundle\Contracts\CrudConfiguratorInterface;

class BrickBuilder
{

    /** @var BrickView[]  */
    private $bricks = [];

    /** @var BrickProvider  */
    private $brickProvider;

    public function __construct(BrickProvider $brickProvider)
    {
        $this->brickProvider = $brickProvider;
    }

    /** @return BrickView[] */
    public function build(PageConfigInterface $crudConfigurator, Request $request): array
    {
        foreach ($crudConfigurator->getBrickConfigs($request) as $brickConfigurator) {
            $brickFactory = $this->brickProvider->getBrick($brickConfigurator);
            if ($brickFactory) {
                $this->bricks[] = $brickFactory->buildView($brickConfigurator);
            } else {
                throw new UnsupportedBrickConfigurationException(get_class($brickConfigurator));
            }
        }
        return $this->bricks;
    }

    public function bindRequest(Request $request): void
    {
    }
}
