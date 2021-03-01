<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto\Layout;

class TitleElement extends AbstractLayoutElement
{

    public static function new(): self
    {
        return new self();
    }

    public function getTemplate(): string
    {
        return 'elements/_title';
    }
}
