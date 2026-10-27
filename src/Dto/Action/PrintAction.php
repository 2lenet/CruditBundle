<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto\Action;

use Lle\CruditBundle\Dto\Icon;

/**
 * Prints the page (a button, no path): the controller print of the bundle, no inline script.
 */
class PrintAction extends AbstractAction
{
    public static function new(string $label = 'crudit.action.print', ?Icon $icon = null): static
    {
        return (new static($label))
            ->setIcon($icon ?? Icon::new('print'))
            ->setTranslationDomain($label === 'crudit.action.print' ? 'LleCruditBundle' : null);
    }
}
