<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Provider;

use Lle\CruditBundle\{
    Contracts\CrudConfigInterface,
    Exception\BadConfigException,
    Layout\AdminLteLayout,
    Layout\LayoutInterface
};
use Symfony\Component\HttpFoundation\Request;

class ConfigProvider
{
    /** @var array */
    private $configurators;

    public function __construct(iterable $configurators)
    {
        foreach ($configurators as $configurator) {
            $this->configurators[get_class($configurator)] = $configurator;
        }
    }

    public function get(string $classname): ?CrudConfigInterface
    {
        return $this->configurators[$classname] ?? null;
    }

    public function getConfigurator(string $resource): ?CrudConfigInterface
    {
        foreach ($this->configurators as $configurator) {
            if ($configurator->getName() === $resource) {
                return $configurator;
            }
        }
        return null;
    }

    public function getConfigurators(): iterable
    {
        return $this->configurators;
    }

    public function getConfiguratorByRequest(Request $request): ?CrudConfigInterface
    {
        return $this->getConfigurator($request->attributes->get('resource'));
    }
}
