<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Provider;

use Lle\CruditBundle\Contracts\MenuProviderInterface;
use Lle\CruditBundle\Dto\{
    Layout\LinkElement,
    Path
};

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
            yield LinkElement::new(
                $this->generateLibelle($configurator->getName()),
                Path::new('lle_crudit_crud_index', ['ressource' => $configurator->getName()])
            );
        }
    }

    public function generateLibelle(string $name): string
    {
        return ucfirst(str_replace('-', ' ', $name));
    }
}
