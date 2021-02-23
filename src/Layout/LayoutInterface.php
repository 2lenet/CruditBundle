<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Layout;

interface LayoutInterface
{
    public function getLayoutTemplate(): string;

    public function getMenuTemplate(): string;

    public function getName(): string;

    public function getMenuItems(): array;
}
