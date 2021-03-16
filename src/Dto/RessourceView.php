<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto;

class RessourceView
{

    /** @var int|string  */
    private $id;

    /** @var object  */
    private $item;

    /** @var FieldView[]  */
    private $fields;

    /** @param int|string $id */
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

    /** @return ?object */
    public function getItem(): ?object
    {
        return $this->item;
    }

    /** @return FieldView[] */
    public function getFields(): array
    {
        return $this->fields;
    }
}
