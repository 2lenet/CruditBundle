<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Lle\CruditBundle\Contracts\FilterSetInterface;
use Symfony\Component\HttpFoundation\Request;
use Lle\CruditBundle\Contracts\FilterTypeInterface;

/**
 * AbstractFilterType
 *
 * Abstract base class for all admin list filters
 */
abstract class AbstractFilterType implements FilterTypeInterface
{
    public function __construct($fieldname)
    {
        $this->columnName = $fieldname;
        $this->id = $fieldname;
        $this->label = "field.".$fieldname;
        $this->alias = "root.";
    }

    protected string $columnName;

    protected bool $hidden = false;

    protected array $additionnal_keys = [];

    protected string $alias = 'root.';

    protected string $id;

    protected string $label = "";

    protected ?array $data = null;

    protected array $default = [];

    public function getOperators(): array
    {
        return [
        ];
    }

    /**
     * @return array
     */
    public function getAdditionnalKeys(): array
    {
        return $this->additionnal_keys;
    }

    /**
     * @param array $additionnal_keys
     * @return AbstractFilterType
     */
    public function setAdditionnalKeys(array $additionnal_keys): AbstractFilterType
    {
        $this->additionnal_keys = $additionnal_keys;
        return $this;
    }

    public function getFilterLabel()
    {
        return $this->label;
    }

    public function setDefault($default_data): self {
        $this->default = $default_data;
        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    /*public function addJoin($queryBuilder)
    {
        $queryHelper = new QueryHelper();
        [
            $alias,
            $col
        ] = $queryHelper->getPath($queryBuilder, $queryBuilder->getRootAlias(), $this->columnName);
        $this->alias = $alias;
        $this->columnName = $col;
    }*/

    /**
     * Returns empty string if no alias, otherwise make sure the alias has just one '.' after it.
     *
     * @return string
     */
    protected function getAlias()
    {
        return $this->alias;
    }

    public function isHidden()
    {
        return $this->hidden;
    }

    public function setHidden($hidden)
    {
        $this->hidden = $hidden;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    /**
     * Get the value of default
     */
    public function getDefault()
    {
        return $this->default;
    }

    public function getStateTemplate(): string
    {
        $fqcn = get_class($this);
        $name = strtolower(preg_replace(
            "/Lle\\\\CruditBundle\\\\Filter\\\\FilterType\\\\(\w+)FilterType/",
            '${1}',
            $fqcn
        ));

        return "@LleCrudit/filter/state/{$name}_filter.html.twig";
    }

    public function getTemplate(): string
    {
        $fqcn = get_class($this);
        $name = strtolower(preg_replace(
            "/Lle\\\\CruditBundle\\\\Filter\\\\FilterType\\\\(\w+)FilterType/",
            '${1}',
            $fqcn
        ));

        return "@LleCrudit/filter/type/{$name}_filter.html.twig";
    }
}
