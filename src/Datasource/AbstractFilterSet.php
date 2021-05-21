<?php


namespace Lle\CruditBundle\Datasource;


class AbstractFilterSet implements \Lle\CruditBundle\Contracts\FilterSetInterface
{

    public function getFilters(): array
    {
        // TODO: Implement getFilters() method.
    }

    public function getId(): string
    {
        $className = get_class($this);
        return strtolower(str_replace("Filterset", "", (substr($className, strrpos($className, '\\') + 1))));
    }
}
