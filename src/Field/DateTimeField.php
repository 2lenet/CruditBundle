<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Field;


use Lle\CruditBundle\Contracts\FieldInterface;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\FieldView;

class DateTimeField extends DateField
{
    
    public function support(string $type): bool
    {
        return (in_array($type, ['datetime', self::class]));
    }

    /** @param mixed $value */
    public function getStringValue($value)
    {
        if ($value instanceof \DateTime) {
            return $value->format('d-m-Y H:i');
        }
        return null;
    }

}
