<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Provider;

use Lle\CruditBundle\{
    Exception\BadConfigException,
    Layout\AdminLteLayout,
    Layout\LayoutInterface
};
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class LayoutProvider
{
    /** @var iterable */
    private $layouts;

    /** @var ParameterBagInterface */
    private $parameterBag;

    public function __construct(iterable $layouts, ParameterBagInterface $parameterBag)
    {
        $this->layouts = $layouts;
        $this->parameterBag = $parameterBag;
    }

    public function getLayout(): LayoutInterface
    {
        $curentLayoutName = $this->getCurrentLayoutName();
        $layoutNames = [];
        foreach ($this->layouts as $layout) {
            if ($layout instanceof LayoutInterface) {
                $layoutNames[] = $layout->getName();
                if ($layout->getName() === $curentLayoutName || get_class($layout) === $curentLayoutName) {
                    return $layout;
                }
            }
        }
        throw new BadConfigException(
            "the layout $curentLayoutName  is not found. Did you mean: " . join(',', $layoutNames)
        );
    }

    private function getCurrentLayoutName(): string
    {
        return $this->parameterBag->get('crudit.layout_provider');
    }
}
