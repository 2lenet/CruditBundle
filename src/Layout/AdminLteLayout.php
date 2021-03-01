<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Layout;

use Lle\CruditBundle\Dto\Badge;
use Lle\CruditBundle\Dto\Icon;
use Lle\CruditBundle\Dto\Layout\HeaderElement;
use Lle\CruditBundle\Dto\Layout\LinkElement;
use Lle\CruditBundle\Dto\Layout\TemplateElement;
use Lle\CruditBundle\Dto\Layout\TitleElement;
use Lle\CruditBundle\Dto\Layout\UserElement;
use Lle\CruditBundle\Dto\Path;
use Lle\CruditBundle\Registry\MenuRegistry;

class AdminLteLayout extends AbstractLayout
{
    /** @var MenuRegistry  */
    private $menuRegistry;

    public function __construct(MenuRegistry $menuRegistry)
    {
        $this->menuRegistry = $menuRegistry;
    }

    public function getTemplateDirectory(): string
    {
        return '@LleCrudit/layout/admin_lte';
    }

    public function getAssetDirectory(): string
    {
        return '/bundles/llecrudit/adminlte';
    }

    public function getAsset(string $name): string
    {
        return $this->getAssetDirectory() . '/' . $name;
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
     * @return array
     */
    public function getElements(string $name): array
    {
        $dashboard = LinkElement::new('Dashboard', new Path('lle_crudit_dashboard_index'), Icon::new('dashboard'));
        $dashboard->addBadge(Badge::new('123'));
        $dashboard->add(
            LinkElement::new(
                'Dashboard 1',
                Path::new('lle_crudit_dashboard_index'),
                Icon::new('circle', 'far')
            )
        );
        $menuItems = [];
        $menuItems[] = HeaderElement::new('title');
        $menuItems[] = $dashboard;
        $menuItems = array_merge($menuItems, $this->menuRegistry->getElements());
        return [
            'menu-nav' => $menuItems,
            'header-nav' => [LinkElement::new('Contact', Path::new('lle_crudit_dashboard_index'))],
            'menu-sidebar' => [UserElement::new()],
            'menu-main-sidebar' => [TitleElement::new('title')],
            'header-right' => [
                TemplateElement::new('elements/_message'),
                TemplateElement::new('elements/_notification')
            ]
        ][$name];
    }

    public function getElementNames(): array
    {
        return [
            'menu-nav',
            'menu-sidebar',
            'menu-main-sidebar',
            'header-nav',
            'header-right'
        ];
    }
}
