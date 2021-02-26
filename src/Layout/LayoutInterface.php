<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Layout;

interface LayoutInterface
{
    public function getTemplate(string $name): string;

    public function getTemplateDirectory(): string;

    public static function getName(): string;

    public function getMenuItems(): array;
}
