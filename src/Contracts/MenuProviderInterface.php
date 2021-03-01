<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

use Lle\CruditBundle\Dto\Layout\LayoutElementInterface;

interface MenuProviderInterface
{
    /** @return LayoutElementInterface[] */
    public function getMenuEntry(): array;
}
