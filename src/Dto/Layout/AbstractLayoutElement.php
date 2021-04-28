<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto\Layout;

abstract class AbstractLayoutElement implements LayoutElementInterface
{

    /** @var array */
    protected $roles;

    /** @var string */
    protected $cssClass;

    /** @var int */
    protected $priority = 1;

    /**  @var string */
    protected $id = null;

    /** @var string */
    protected $parent = null;

    /** @var LayoutElementInterface[] */
    protected $children = [];

    /**
     * @return string[]
     */
    public function getRole(): ?array
    {
        return $this->roles;
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
