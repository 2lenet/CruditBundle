<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Provider;

use Lle\CruditBundle\{
    Contracts\CrudConfiguratorInterface,
    Exception\BadConfigException,
    Layout\AdminLteLayout,
    Layout\LayoutInterface
};
use Symfony\Component\HttpFoundation\Request;

class ConfiguratorProvider
{
    /** @var iterable */
    private $configurators;

    public function __construct(iterable $configurators)
    {
        $this->configurators = $configurators;
    }

    public function getConfigurator(string $ressource): ?CrudConfiguratorInterface
    {
        foreach ($this->configurators as $configurator) {
            if ($configurator->getName() === $ressource) {
                return $configurator;
            }
        }
        return null;
    }

    public function getConfigurators(): iterable
    {
        return $this->configurators;
    }

    public function getConfiguratorByRequest(Request $request): ?CrudConfiguratorInterface
    {
        return $this->getConfigurator($request->attributes->get('ressource'));
    }
}
