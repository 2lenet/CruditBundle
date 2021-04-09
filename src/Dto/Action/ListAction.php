<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto\Action;

use Lle\CruditBundle\Dto\Icon;
use Lle\CruditBundle\Dto\Path;

class ListAction extends AbstractAction
{
    public static function new(string $label, Path $path, ?Icon $icon = null): self
    {
        return (new self($label, $path))
            ->setIcon($icon)
            ->setHideLabel(false);
    }
}
