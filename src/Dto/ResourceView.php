<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto;

class ResourceView
{
    private int|string $id;
    private object $resource;
    /** @var FieldView[] */
    private array $fields;

    private ?RuptBreak $ruptGroup1 = null;

    private ?RuptBreak $ruptGroup2 = null;

    /**
     * @param int|string $id
     */
    public function __construct($id, object $resource, array $fields)
    {
        $this->id = $id;
        $this->resource = $resource;
        $this->fields = $fields;
    }

    public function getId(): int|string
    {
        return $this->id;
    }

    public function getResource(): object
    {
        return $this->resource;
    }

    /** @return FieldView[] */
    public function getFields(): array
    {
        return $this->fields;
    }

    public function getRuptGroup1(): ?RuptBreak
    {
        return $this->ruptGroup1;
    }

    public function setRuptGroup1(string $display, string $key, string $cssClass = ''): self
    {
        $this->ruptGroup1 = new RuptBreak($display, $key, $cssClass);

        return $this;
    }

    public function getRuptGroup2(): ?RuptBreak
    {
        return $this->ruptGroup2;
    }

    public function setRuptGroup2(string $display, string $key, string $cssClass = ''): self
    {
        $this->ruptGroup2 = new RuptBreak($display, $key, $cssClass);

        return $this;
    }

    public function getLinkId(string $fieldName): mixed
    {
        if ($fieldName === 'id') {
            return $this->getId();
        }

        $keys = explode(':', $fieldName);
        $fieldName = $keys[0];
        unset($keys[0]);

        foreach ($this->fields as $field) {
            if ($field->getField()->getName() == $fieldName) {
                return $this->getValue($keys, $field->getRawValue());
            }
        }

        return null;
    }

    private function getValue(array $keys, mixed $value): mixed
    {
        $key = array_key_first($keys);
        $value = $value->{'get' . ucfirst($keys[$key])}();

        if (count($keys) > 1) {
            unset($keys[$key]);

            return $this->getValue($keys, $value);
        }

        return $value;
    }
}
