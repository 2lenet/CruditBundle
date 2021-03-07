<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Builder;

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
    public function build(CrudConfiguratorInterface $crudConfigurator, Request $request): array
    {
        $this->initBrick($crudConfigurator);
        $this->bindRequest($request);
        return $this->bricks;
    }

    public function initBrick(CrudConfiguratorInterface $crudConfigurator): void
    {
        $mainClass = $crudConfigurator->getSubjectClass();
        foreach ($crudConfigurator->getBrickConfigurators() as $brickConfigurator) {
            $brickConfigurator->setMainSubjectClass($mainClass);
            $brick = $this->brickProvider->getBrick($brickConfigurator);
            if ($brick) {
                $this->bricks[] = $brick->buildView($brickConfigurator);
            } else {
                throw new UnsupportedBrickConfigurationException(get_class($brickConfigurator));
            }
        }
    }

    public function bindRequest(Request $request): void
    {
    }
}
