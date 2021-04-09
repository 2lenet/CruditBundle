<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Field;

use Lle\CruditBundle\Contracts\FieldInterface;
use Lle\CruditBundle\Dto\FieldView;

class TextField implements FieldInterface
{

    public function support(string $type): bool
    {
        return (in_array($type, ['integer', 'float', 'string', 'decimal', 'text', self::class]));
    }

    /** @param mixed $value */
    public function buildView(FieldView $fieldView, $value): FieldView
    {
        return $fieldView->setStringValue((string) $value);
    }
}
