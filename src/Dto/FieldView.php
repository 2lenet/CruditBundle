<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto;

use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Dto\Field\Field;

class FieldView
{
    private Field $field;
    private mixed $value;
    private ?string $stringValue;
    private mixed $options;
    private ?object $resource = null;
    private ?CrudConfigInterface $config = null;

    public function __construct(Field $field, mixed $value)
    {
        $this->field = $field;
        $this->value = $value;
    }

    public function setResource(?object $resource): self
    {
        $this->resource = $resource;

        return $this;
    }

    public function getResource(): ?object
    {
        return $this->resource;
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

    public function getRawValue(): mixed
    {
        return $this->value;
    }

    public function getField(): Field
    {
        return $this->field;
    }

    public function getOptions(): mixed
    {
        return $this->options;
    }

    public function setOptions(mixed $options): self
    {
        $this->options = $options;

        return $this;
    }

    public function getConfig(): ?CrudConfigInterface
    {
        return $this->config;
    }

    public function setConfig(?CrudConfigInterface $config): self
    {
        $this->config = $config;

        return $this;
    }

    public function isEditable(mixed $resource = null): bool
    {
        return !$this->field->getEditableIf() || call_user_func($this->field->getEditableIf(), $resource);
    }
}
