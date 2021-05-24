<?php

namespace Lle\CruditBundle\Filter\FilterType;

/**
 * BooleanFilterType
 */
class BooleanFilterType extends AbstractFilterType
{
    public static function new(string $fieldname): self
    {
        return new self($fieldname);
    }

    public function __construct($fieldname)
    {
        $this->columnName = $fieldname;
        $this->id = $fieldname;
        $this->label = "field.".$fieldname;
        $this->alias = "root.";
    }

    public function configure(array $config = [])
    {
        parent::configure($config);
        $this->defaults['value'] = $config['default_value'] ?? 'all';
    }

    public function apply($queryBuilder)
    {
        $value = $this->data['value'];
        if (isset($value) && $value != 'all') {
            switch ($value) {
                case 'true':
                    $queryBuilder->andWhere($queryBuilder->expr()->eq($this->alias . $this->columnName, 'true'));
                    break;
                case 'false':
                    $queryBuilder->andWhere($queryBuilder->expr()->eq($this->alias . $this->columnName, 'false'));
                    break;
            }
        }
    }

    public function isSelected($data, $value)
    {
        if (! isset($data['value'])) {
            return ($this->defaults['value'] == $value);
        } else {
            return ($data['value'] == $value);
        }
        return false;
    }

    public function getStateTemplate()
    {
        return '@LleCrudit/filter/state/boolean_filter.html.twig';
    }

    public function getTemplate()
    {
        return '@LleCrudit/filter/type/boolean_filter.html.twig';
    }
}
