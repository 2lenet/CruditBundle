<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto\Layout;

interface LayoutElementInterface
{
    public function getTemplate(): string;
    public function getPriority(): int;
}
