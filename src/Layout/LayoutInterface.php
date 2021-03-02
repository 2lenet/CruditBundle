<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Layout;

use Lle\CruditBundle\Dto\Layout\LinkElement;
use Symfony\Component\HttpFoundation\Request;

interface LayoutInterface
{
    public function getTemplate(string $name): string;

    public function getTemplateDirectory(): string;

    public static function getName(): string;

    public function getElements(string $name): array;

    public function isActive(LinkElement $item, Request $request): bool;

    public function getElementNames(): array;
}
