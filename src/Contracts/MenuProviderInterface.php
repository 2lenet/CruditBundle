<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

interface MenuProviderInterface
{
    /** @return LayoutElementInterface[] */
    public function getMenuEntry(): iterable;
}
