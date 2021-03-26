<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Field;

use Lle\CruditBundle\Contracts\FieldInterface;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\FieldView;

class DateField implements FieldInterface
{

    public function support(string $type): bool
    {
        return (in_array($type, ['date', self::class]));
    }

    /** @param mixed $value */
    public function buildView(Field $field, $value): FieldView
    {
        return new FieldView(
            $field,
            $value,
            $this->getStringValue($value)
        );
    }

    /** @param mixed $value */
    public function getStringValue($value)
    {
        if ($value instanceof \DateTime) {
            $value = $value->format('d-m-Y');
        }
        return null;
    }

}
