<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Registry;

use Lle\CruditBundle\Contracts\LayoutElementInterface;
use Lle\CruditBundle\Contracts\MenuProviderInterface;

class MenuRegistry
{
    /** @var iterable */
    private $providers;

    public function __construct(iterable $providers)
    {
        $this->providers = $providers;
    }

    /** @param LayoutElementInterface[] $entries */
    public function getElement(iterable $entries, string $id): ?LayoutElementInterface
    {
        foreach ($entries as $element) {
            if ($element->getId() === $id) {
                return $element;
            }
        }

        return null;
    }

    public function getElements($navid): array
    {
        $elements = [];
        foreach ($this->providers as $k => $provider) {
            if ($provider instanceof MenuProviderInterface) {
                $entries = $provider->getMenuEntry();
                foreach ($entries as $kk => $element) {
                    /** @var LayoutElementInterface $element */
                    if ($element->getParent() !== null) {
                        $parent = $this->getElement($entries, $element->getParent());
                        if ($parent) {
                            $parent->addChild($element);
                        }
                    } else {
                        $elements[$element->getPriority() * 1000 + ($k * 100) + $kk] = $element;
                    }
                }
            }
        }
        ksort($elements);

        return $elements;
    }
}
