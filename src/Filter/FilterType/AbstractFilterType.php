<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Lle\CruditBundle\Contracts\FilterSetInterface;
use Symfony\Component\HttpFoundation\Request;
use Lle\CruditBundle\Lib\QueryHelper;
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

    /**
     *
     * @var null|string
     */
    protected $columnName = null;

    protected $hidden = false;

    protected $additionnal_keys = [];


    /**
     *
     * @var null|string
     */
    protected $alias = null;

    protected $id = null;

    protected $label = null;

    protected $request = null;

    protected $data = null;

    protected $data_keys = [];

    protected $defaults = [];

    /**
     *
     * @var bool
     */
    protected $head = false;

    /**
     *
     * @param string $columnName
     *            The column name
     * @param string $alias
     *            The alias
     */
    public function init($columnName, $label = null, $alias = 'entity')
    {
        $this->columnName = $columnName;
        $this->id = str_replace('.', '_', $columnName);
        $this->alias = $alias;
        $this->label = $label ?? "label." . $columnName;
        $this->data = [];
        $this->data_keys = [
            'op',
            'value'
        ];
    }

    public function getOperators()
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
    public function configure(array $config = [])
    {
        $this->hidden = $config['hidden'] ?? false;
        $this->head = $config['head'] ?? false;
    }

    public function getFilterLabel()
    {
        return $this->label;
    }

    public function getCode()
    {
        return $this->columnName;
    }

    public function getId()
    {
        return $this->id;
    }


    public function addJoin($queryBuilder)
    {
        $queryHelper = new QueryHelper();
        [
            $alias,
            $col
        ] = $queryHelper->getPath($queryBuilder, $queryBuilder->getRootAlias(), $this->columnName);
        $this->alias = $alias;
        $this->columnName = $col;
    }

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

    /**
     *
     * @return bool
     */
    public function isHead()
    {
        return $this->head;
    }

    /**
     *
     * @param bool $head
     */
    public function setHead($head)
    {
        $this->head = $head;
    }

    public function setRequest($request)
    {
        $this->request = $request;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getStateTemplate()
    {
        return '@LleCrudit/filter/state/string_filter.html.twig';
    }

    /**
     * Get the value of defaults
     */
    public function getDefaults()
    {
        return $this->defaults;
    }

    /**
     * Set the value of defaults
     *
     * @return self
     */
    public function setDefaults($defaults)
    {
        if (!is_array($defaults)) {
            $defaults = ['value' => $defaults];
        }
        $this->defaults = array_merge($this->defaults, $defaults);

        return $this;
    }
}
