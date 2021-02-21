<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Provider;

use Lle\CruditBundle\Dto\MenuItem;
use Lle\CruditBundle\Dto\Path;

class DefaultLayoutProvider
{

    public function getLayoutTemplate(): string
    {
        return '@LleCrudit/layout/default/layout.html.twig';
    }

    public function getMenuTemplate(): string
    {
        return '@LleCrudit/layout/default/menu.html.twig';
    }

    public function getName(): string
    {
        return 'default';
    }

    /**
     * @return MenuItem[]
     */
    public function getMenuItems(): array
    {
        $menuItems = [];
        $menuItems[] = MenuItem::new('Dashboard', new Path('app_default_index'));
        return $menuItems;
    }
}
