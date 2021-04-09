<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

use Lle\CruditBundle\Dto\FieldView;

interface FieldInterface
{
    public function support(string $type): bool;

    /** @param mixed $value */
    public function buildView(FieldView $field, $value): FieldView;
}
