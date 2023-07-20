<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto;

class Path
{
    private string $route;

    private array $params;

    private ?string $role = null;

    public static function new(string $route, array $params = []): self
    {
        return new self(strtolower($route), $params);
    }

    public function __construct(string $route, array $params = [])
    {
        $this->route = $route;
        $this->params = $params;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function getParams(array $params = []): array
    {
        return array_merge($this->params, $params);
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): self
    {
        $this->role = $role;

        return $this;
    }
}
