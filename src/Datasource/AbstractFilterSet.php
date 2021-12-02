<?php

namespace Lle\CruditBundle\Datasource;

use Lle\CruditBundle\Contracts\FilterSetInterface;

class AbstractFilterSet implements FilterSetInterface
{
    public function getFilters(): array
    {
        return [];
    }

    public function getNumberDisplayed(): int
    {
        return 4;
    }

    public function getId(): string
    {
        $className = get_class($this);

        return strtolower(str_replace("FilterSet", "", (substr($className, strrpos($className, '\\') + 1))));
    }
}
