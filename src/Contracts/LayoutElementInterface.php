<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

interface LayoutElementInterface
{
    public function getTemplate(): string;

    public function getPriority(): int;

    public function getId(): ?string;

    public function getParent(): ?string;

    /** @return LayoutElementInterface[] */
    public function getChildren(): array;

    public function addChild(LayoutElementInterface $child): self;

    /**
     * Copy of the element with other children, the element is not changed: the menu shown to the user (MenuBuilder)
     * only keeps the children the user is granted.
     *
     * @param LayoutElementInterface[] $children
     */
    public function withChildren(array $children): static;
}
