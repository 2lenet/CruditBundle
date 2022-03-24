<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto;

class ResourceView
{
    /** @var int|string */
    private $id;

    /** @var object */
    private $resource;

    /** @var FieldView[] */
    private $fields;

    /**
     * @param int|string $id
     */
    public function __construct($id, object $resource, array $fields)
    {
        $this->id = $id;
        $this->resource = $resource;
        $this->fields = $fields;
    }

    /** @return int|string */
    public function getId()
    {
        return $this->id;
    }

    /** @return object */
    public function getResource(): object
    {
        return $this->resource;
    }

    /** @return FieldView[] */
    public function getFields(): array
    {
        return $this->fields;
    }

    public function getLinkId(string $fieldName)
    {
        if ($fieldName === 'id') {
            return $this->getId();
        }
        $keys = explode(':', $fieldName);
        if (!isset($keys[0])) {
            return null;
        }

        $fieldName = $keys[0];

        unset($keys[0]);

        foreach ($this->fields as $field) {
            if ($field->getField()->getName() == $fieldName) {
                return $this->getValue($keys, $field->getRawValue());
            }
        }
        return null;
    }

    private function getValue(array $keys, object $value)
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
