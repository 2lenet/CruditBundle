<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto\Layout;

interface LayoutElementInterface
{
    public function getTemplate(): string;

    public function getPriority(): int;

    public function getId(): ?string;

    public function getParent(): ?string;

    /** @return LayoutElementInterface[] */
    public function getChildren(): array;

    public function addChild(LayoutElementInterface $child): self;
}
