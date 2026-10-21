<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto\Layout;

class HorizontalSeparatorElement extends AbstractLayoutElement
{
    public static function new(): self
    {
        return new self();
    }

    public function getTemplate(): string
    {
        return '@LleCrudit/layout/sidebar/_separator.html.twig';
    }
}
