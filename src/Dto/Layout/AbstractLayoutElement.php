<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto\Layout;

use Lle\CruditBundle\Contracts\LayoutElementInterface;

abstract class AbstractLayoutElement implements LayoutElementInterface
{
    protected ?string $role = null;

    protected ?string $cssClass = null;

    protected int $priority = 1;

    protected ?string $id = null;

    protected ?string $parent = null;

    /** @var LayoutElementInterface[] */
    protected array $children = [];

    public function getRole(): string
    {
        return $this->role ?? "ROLE_USER";
    }

    public function setRole(?string $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getCssClass(): ?string
    {
        return $this->cssClass;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getParent(): ?string
    {
        return $this->parent;
    }

    public function setParent(string $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function addChild(LayoutElementInterface $element): self
    {
        $this->children[] = $element;

        return $this;
    }

    /** @return LayoutElementInterface[] */
    public function getChildren(): array
    {
        return $this->children;
    }
}
