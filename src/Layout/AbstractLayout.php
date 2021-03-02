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
        if ($item->getPath() !== null) {
            $r2 = $request->get('_route');
            $r1 = $item->getPath()->getRoute();
            $p1 = (strrpos($r1, '_')) ? strrpos($r1, '_') : strlen($r1);
            $p2 = (strrpos($r2, '_')) ? strrpos($r1, '_') : strlen($r2);
            return
                (substr($r1, 0, $p1)) ===
                (substr($r2, 0, $p2));
        }
        return false;
    }
}
