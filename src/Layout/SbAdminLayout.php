<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Layout;

use Lle\CruditBundle\Dto\Layout\SearchElement;
use Lle\CruditBundle\Dto\Layout\TemplateElement;
use Lle\CruditBundle\Dto\Layout\UserElement;
use Lle\CruditBundle\Dto\Layout\VerticalSeparatorElement;
use Lle\CruditBundle\Registry\MenuRegistry;

class SbAdminLayout extends AbstractLayout
{
    /** @var MenuRegistry  */
    private $menuRegistry;

    public function __construct(MenuRegistry $menuRegistry)
    {
        $this->menuRegistry = $menuRegistry;
    }

    public function getTemplateDirectory(): string
    {
        return '@LleCrudit/layout/sb_admin';
    }

    public function getAssetDirectory(): string
    {
        return '/bundles/llecrudit/sbadmin';
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
        return 'sb-admin';
    }

    /**
     * @return array
     */
    public function getElements(string $name): array
    {
        return [
            'menu-nav' => $this->menuRegistry->getElements(),
            'header-nav' => [SearchElement::new()],
            'header-right' => [
                TemplateElement::new('elements/_message'),
                TemplateElement::new('elements/_notification'),
                VerticalSeparatorElement::new(),
                UserElement::new()
            ]
        ][$name];
    }

    public function getElementNames(): array
    {
        return [
            'menu-nav',
            'header-nav',
            'header-right'
        ];
    }
}
