<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Layout;

use Lle\CruditBundle\Dto\Layout\LinkElement;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractLayout implements LayoutInterface
{
    public function getTemplate(string $name): string
    {
        return $this->getTemplateDirectory() . "/" . $name . ".html.twig";
    }

    public function getLayout(): string
    {
        return $this->getTemplate('layout');
    }

    public function isActive(LinkElement $item, Request $request): bool
    {

        if (\count($item->getChildren()) > 0) {
            foreach ($item->getChildren() as $child) {
                if ($this->isActive($child, $request)) {
                    return true;
                }
            }
        }

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
        } elseif ($item->getPath() !== null &&  $item->getPath()->getRoute() === 'lle_crudit_crud_index') {
            return $item->getPath()->getParams()['resource'] === $request->get('resource');
        }
        return false;
    }
}
