<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto;

class RessourceView
{

    /** @var int|string  */
    private $id;

    /** @var object|array  */
    private $item;

    /** @var FieldView[]  */
    private $fields;

    /**
     * @param int|string $id
     * @param object|array $item
     */
    public function __construct($id, $item, array $fields)
    {
        $this->id = $id;
        $this->item = $item;
        $this->fields = $fields;
    }

    /** @return int|string */
    public function getId()
    {
        return $this->id;
    }

    /** @return array|object */
    public function getItem()
    {
        return $this->item;
    }

    /** @return FieldView[] */
    public function getFields(): array
    {
        return $this->fields;
    }
}
