<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

interface QueryAdapterInterface
{
    public function where(string $string): self;

    /** @param mixed $value */
    public function setParameter(string $parameter, $value): self;

    public function execute(): array;
}
