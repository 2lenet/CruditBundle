<?php

namespace Lle\CruditBundle\Datasource;

class DatasourceFilter
{
    private $fieldname;
    private $fieldvalue;
    private $alias;
    private $operator;

    /**
     * @return mixed
     */
    public function getField()
    {
        return $this->fieldname;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->fieldvalue;
    }

    /**
     * @return mixed|string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    public function getOperator()
    {
        return $this->operator;
    }

    public function setOperator(string $operator)
    {
        $this->operator = $operator;

        return $operator;
    }

    public function __construct($fieldname, $fieldvalue, $alias = "root", $operator = "=")
    {
        $this->fieldname = $fieldname;
        $this->fieldvalue = $fieldvalue;
        $this->alias = $alias;
        $this->operator = $operator;
    }
}
