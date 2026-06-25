<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Registry;

class IconRegistry
{
    /** @param array<string, string> $icons */
    public function __construct(private array $icons)
    {
    }

    public function get(string $name): string
    {
        return $this->icons[$name] ?? $name;
    }

    public function has(string $name): bool
    {
        return isset($this->icons[$name]);
    }

    /** @return array<string, string> */
    public function all(): array
    {
        return $this->icons;
    }
}
