<?php


namespace Lle\CruditBundle\Datasource;


class FilterParams
{
    protected $field;
    protected $operator;
    protected $value;
    protected $alias=null;
    
    public function __construct(string $id, $operator, $value) {
        $this->field = $field;
        $this->operator = $operator;
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @param mixed $field
     * @return FilterParams
     */
    public function setField($field)
    {
        $this->field = $field;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @param mixed $operator
     * @return FilterParams
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     * @return FilterParams
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return null
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @param null $alias
     * @return FilterParams
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
        return $this;
    }
}
