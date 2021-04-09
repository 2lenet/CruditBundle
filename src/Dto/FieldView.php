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

    /** @var ?object */
    private $resource = null;

    /** @var ?object */
    private $parentResource = null;

    /** @param mixed $value */
    public function __construct(Field $field, $value)
    {
        $this->field = $field;
        $this->value = $value;
    }

    public function setResource(object $resource): self
    {
        $this->resource = $resource;
        return $this;
    }

    public function setParentResource(object $resource): self
    {
        $this->resource = $resource;
        return $this;
    }

    public function getResource(): ?object
    {
        return $this->resource;
    }

    public function getParentResource(): ?object
    {
        return $this->parentResource;
    }

    public function setStringValue(?string $stringValue): self
    {
        $this->stringValue = $stringValue;
        return $this;
    }

    public function getValue(): ?string
    {
        return $this->stringValue;
    }

    /** @return mixed */
    public function getRawValue()
    {
        return $this->value;
    }

    public function getField(): Field
    {
        return $this->field;
    }
}
