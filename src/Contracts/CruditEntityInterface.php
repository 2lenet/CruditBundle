<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

interface CruditEntityInterface
{
    public function __toString(): string;

    public function canEdit(): bool;

    public function canDelete(): bool;
}