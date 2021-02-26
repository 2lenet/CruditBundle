<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Layout;

use Lle\CruditBundle\Dto\MenuItem;
use Lle\CruditBundle\Dto\Path;

class AdminLteLayout extends AbstractLayout
{

    public function getTemplateDirectory(): string
    {
        return '@LleCrudit/layout/admin_lte';
    }

    public function getOptions(): array
    {
        return [
            'theme' => 'light' //or dark
        ];
    }

    public static function getName(): string
    {
        return 'admin-lte';
    }

    /**
     * @return MenuItem[]
     */
    public function getMenuItems(): array
    {
        $menuItems = [];
        $menuItems[] = MenuItem::new('Dashboard', new Path('lle_crudit_dashboard_index'));
        return $menuItems;
    }
}
