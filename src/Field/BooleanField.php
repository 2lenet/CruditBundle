<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Field;

use Lle\CruditBundle\Contracts\FieldInterface;
use Lle\CruditBundle\Dto\FieldView;

class BooleanField implements FieldInterface
{

    public function support(string $type): bool
    {
        return (in_array($type, ['boolean', 'bool', self::class]));
    }

    /** @param mixed $value */
    public function buildView(FieldView $fieldView, $value): FieldView
    {
        return $fieldView->setStringValue(($value) ? 'crudit.yes' : 'crudit.no');
    }
}
