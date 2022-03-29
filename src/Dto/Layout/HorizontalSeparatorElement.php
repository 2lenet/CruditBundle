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
        return '@LleCrudit/layout/sb_admin/elements/_h_separator.html.twig';
    }
}
