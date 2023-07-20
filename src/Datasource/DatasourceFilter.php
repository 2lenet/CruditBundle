<?php

namespace Lle\CruditBundle\Datasource;

class DatasourceFilter
{
    private mixed $fieldname;

    private mixed $fieldvalue;

    private string $alias;

    private string $operator;

    public function __construct(mixed $fieldname, mixed $fieldvalue, string $alias = "root", string $operator = "=")
    {
        $this->fieldname = $fieldname;
        $this->fieldvalue = $fieldvalue;
        $this->alias = $alias;
        $this->operator = $operator;
    }

    public function getField(): mixed
    {
        return $this->fieldname;
    }

    public function getValue(): mixed
    {
        return $this->fieldvalue;
    }

    public function getAlias(): mixed
    {
        return $this->alias;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function setOperator(string $operator): string
    {
        $this->operator = $operator;

        return $operator;
    }
}
