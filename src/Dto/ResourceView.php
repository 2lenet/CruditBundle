<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto;

class ResourceView
{

    /** @var int|string  */
    private $id;

    /** @var object */
    private $resource;

    /** @var FieldView[]  */
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
}
