<?php

namespace Lle\CruditBundle\Twig;

use Lle\CruditBundle\Dto\Layout\LinkElement;
use Lle\CruditBundle\Registry\MenuRegistry;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class CruditExtension extends AbstractExtension
{
    private MenuRegistry $menuRegistry;

    public function __construct(MenuRegistry $menuRegistry)
    {
        $this->menuRegistry = $menuRegistry;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('crudit_menu_items', [$this, 'menuItems']),
            new TwigFunction('crudit_menu_active', [$this, 'menuIsActive']),

        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('json_decode', [$this, 'jsonDecode']),
            new TwigFilter("get_crudit_routename", [$this, "getCruditRoutename"]),
        ];
    }

    public function menuItems($navid)
    {
        return $this->menuRegistry->getElements($navid);
    }

    public function menuIsActive($item, $request)
    {
        if (\count($item->getChildren()) > 0) {
            foreach ($item->getChildren() as $child) {
                if ($this->menuIsActive($child, $request)) {
                    return true;
                }
            }
        }

        if ($item instanceof LinkElement) {
            if ($item->getPath() !== null && $item->getPath()->getRoute() !== 'lle_crudit_crud_index') {
                $currentRoute = $request->get('_route');
                $linkRoute = $item->getPath()->getRoute();
                $positionLastUnderscoreCurrentRoute = (strrpos($currentRoute, '_')) ?
                    (int)strrpos($currentRoute, '_') :
                    strlen($currentRoute);
                $positionLastUnderscoreLinkRoute = (strrpos($linkRoute, '_')) ?
                    (int)strrpos($linkRoute, '_') :
                    strlen($linkRoute);
                return
                    (substr($currentRoute, 0, $positionLastUnderscoreCurrentRoute)) ===
                    (substr($linkRoute, 0, $positionLastUnderscoreLinkRoute));
            } elseif ($item->getPath() !== null && $item->getPath()->getRoute() === 'lle_crudit_crud_index') {
                return $item->getPath()->getParams()['resource'] === $request->get('resource');
            }
        }
        return false;
    }

    public function jsonDecode($value)
    {
        return json_decode($value, true);
    }

    public function getCruditRoutename(object $value): string
    {
        $class = (new \ReflectionClass($value))->getShortName();

        return 'app_crudit_' . strtolower($class) . '_show';
    }
}
