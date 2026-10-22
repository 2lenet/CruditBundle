<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Menu;

use Lle\CruditBundle\Contracts\LayoutElementInterface;
use Lle\CruditBundle\Dto\Layout\LinkElement;
use Symfony\Component\HttpFoundation\Request;

/**
 * Is an element of the menu the current page, or does it hold it (a sub-menu, a title)? Used by the menu
 * (`aria-current`, sub-menu open) and by the breadcrumb.
 * - a link to a CRUD (`lle_crudit_crud_index`): the same resource (a link without resource is never active);
 * - a link to the list of a CRUD (`<prefix>_<name>_index`): every route of its CRUD (`app_client_index` is active on
 *   `app_client_show`, `_edit`, and on the actions of the project, `app_client_import`); `app_index` has no name of a
 *   CRUD, it is active on itself only;
 * - another link: its route only. Until 2.0 every route was cut at its last `_`, so `app_home` was active on every
 *   `app_…` route.
 */
class ActiveMenuMatcher
{
    public function isActive(LayoutElementInterface $element, Request $request): bool
    {
        foreach ($element->getChildren() as $child) {
            if ($this->isActive($child, $request)) {
                return true;
            }
        }

        $path = $element instanceof LinkElement ? $element->getPath() : null;
        if ($path === null) {
            return false;
        }

        if ($path->getRoute() === 'lle_crudit_crud_index') {
            $resource = $path->getParams()['resource'] ?? null;

            return $resource !== null && $resource === $request->attributes->get('resource');
        }

        $route = (string) $request->attributes->get('_route');
        $link = $path->getRoute();
        // The routes of the CRUD: its name without `index`, then an action (`app_client_` + `show`)
        $crud = substr($link, 0, -\strlen('index'));
        if (!str_ends_with(strtolower($link), '_index') || !str_contains(rtrim($crud, '_'), '_')) {
            return $route === $link;
        }

        return str_starts_with($route, $crud) && !str_contains(substr($route, \strlen($crud)), '_');
    }
}
