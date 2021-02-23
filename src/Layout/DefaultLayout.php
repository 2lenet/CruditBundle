<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Layout;

use Lle\CruditBundle\Dto\MenuItem;
use Lle\CruditBundle\Dto\Path;

class DefaultLayout implements LayoutInterface
{

    public function getLayoutTemplate(): string
    {
        return '@LleCrudit/layout/default/layout.html.twig';
    }

    public function getMenuTemplate(): string
    {
        return '@LleCrudit/layout/default/menu.html.twig';
    }

    public function getFooterTemplate(): string
    {
        return '@LleCrudit/layout/default/footer.html.twig';
    }

    public function getHeaderTemplate(): string
    {
        return '@LleCrudit/layout/default/header.html.twig';
    }

    public function getFormThemeTemplate(): array
    {
        return [
            '@LleCrudit/layout/default/form_theme.html.twig'
        ];
    }

    public function getOptions(): array
    {
        return [
            'theme' => 'light' //or dark
        ];
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
        $menuItems[] = MenuItem::new('Dashboard', new Path('lle_crudit_dashboard_index'));
        return $menuItems;
    }
}
