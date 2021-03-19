<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Registry;

use Lle\CruditBundle\Contracts\MenuProviderInterface;
use Lle\CruditBundle\Dto\Layout\LayoutElementInterface;

class MenuRegistry
{
    /** @var iterable */
    private $providers;

    public function __construct(iterable $providers)
    {
        $this->providers = $providers;
    }

    public function getElements(): array
    {
        $elements = [];
        foreach ($this->providers as $k => $provider) {
            if ($provider instanceof MenuProviderInterface) {
                foreach ($provider->getMenuEntry() as $kk => $element) {
                    /** @var LayoutElementInterface $element */
                    $elements[$element->getPriority() * 1000 + ($k * 100) + $kk] = $element;
                }
            }
        }
        ksort($elements);
        return $elements;
    }
}
