<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Registry;

use Lle\CruditBundle\Contracts\FieldInterface;
use Lle\CruditBundle\Exception\CruditException;

class FieldRegistry
{
    /** @var iterable */
    private $fields;

    public function __construct(iterable $fields)
    {
        $this->fields = $fields;
    }

    public function get(string $type): FieldInterface
    {
        foreach ($this->fields as $field) {
            if ($field instanceof FieldInterface && $field->support($type)) {
                return $field;
            }
        }
        throw new CruditException('field ' . $type . ' not found');
    }
}
