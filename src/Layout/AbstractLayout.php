<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Layout;

abstract class AbstractLayout implements LayoutInterface
{
    public function getTemplate(string $name): string
    {
        return $this->getTemplateDirectory(). "/" . $name . ".html.twig";
    }

    public function getLayout(): string
    {
        return $this->getTemplate('layout');
    }
}
