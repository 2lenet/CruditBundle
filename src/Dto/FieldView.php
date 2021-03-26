<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto;

use Lle\CruditBundle\Dto\Field\Field;

class FieldView
{

    /** @var Field  */
    private $field;

    /** @var mixed */
    private $value;

    /** @var ?string */
    private $stringValue;

    /** @param mixed $value */
    public function __construct(Field $field, $value, ?string $stringValue)
    {
        $this->field = $field;
        $this->value = $value;
        $this->stringValue = $stringValue;
    }


    public function getValue(): ?string
    {
        return $this->stringValue;
    }

    /** @return mixed */
    public function getRawValue()
    {
        return $this->Value;
    }

    public function getField(): Field
    {
        return $this->field;
    }
}
