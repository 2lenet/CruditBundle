<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto;

use Lle\CruditBundle\Dto\Field\Field;

class FieldView
{

    /** @var Field  */
    private $field;

    /** @var int|string|null */
    private $value;

    /** @param int|string|null $value */
    public function __construct(Field $field, $value)
    {
        $this->field = $field;
        $this->value = $value;
    }

    /** @return int|string|null */
    public function getValue()
    {
        return $this->value;
    }

    public function getField(): Field
    {
        return $this->field;
    }
}
