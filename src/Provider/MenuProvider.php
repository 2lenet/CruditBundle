<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Provider;

use Lle\CruditBundle\Contracts\MenuProviderInterface;
use Lle\CruditBundle\Dto\Path;

class MenuProvider implements MenuProviderInterface
{
    /** @var ConfiguratorProvider  */
    private $configuratorProvider;

    public function __construct(ConfiguratorProvider $configuratorProvider)
    {
        $this->configuratorProvider = $configuratorProvider;
    }

    public function getMenuEntry(): iterable
    {
        foreach ($this->configuratorProvider->getConfigurators() as $configurator) {
            $path = Path::new('lle_crudit_crud_index', ['ressource' => $configurator->getName()]);
            if ($configurator->getLinkElement($path)) {
                yield $configurator->getLinkElement(
                    $path
                );
            }
        }
    }
}
