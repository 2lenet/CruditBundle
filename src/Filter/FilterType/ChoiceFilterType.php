<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Doctrine\ORM\QueryBuilder;

/**
 * ChoiceFilterType
 *
 * For predefined select fields.
 */
class ChoiceFilterType extends AbstractFilterType
{
    protected array $choices;
    protected bool $multiple;

    /**
     * @param string $fieldname
     * @param array $choices
     * @param bool $isMultiple
     * @return ChoiceFilterType
     */
    public static function new(string $fieldname, array $choices, bool $isMultiple = false): ChoiceFilterType
    {
        $f = new self($fieldname);
        $f->setChoices($choices);
        $f->setMultiple($isMultiple);

        return $f;
    }

    /**
     * @param array $choices
     */
    public function setChoices(array $choices): void
    {
        $this->choices = [];
        if (!$this->isAssoc($choices)) {
            foreach ($choices as $choice) {
                $this->choices[$choice] = $choice;
            }
        } else {
            $this->choices = $choices;
        }
    }

    /**
     * @param bool $isMultiple
     */
    public function setMultiple(bool $isMultiple): void
    {
        $this->multiple = $isMultiple;
    }

    public function apply(QueryBuilder $queryBuilder): void
    {
        [$column, $alias, $paramname] = $this->getQueryParams($queryBuilder);

        if (isset($this->data['value']) && $this->data['value']) {
            if ($this->getMultiple()) {
                $queryBuilder->andWhere($queryBuilder->expr()->in($alias . $column, ':' . $paramname));
            } else {
                $queryBuilder->andWhere($queryBuilder->expr()->eq($alias . $column, ':' . $paramname));
            }
            $queryBuilder->setParameter($paramname, $this->data['value']);
        }
    }

    public function getChoices(): array
    {
        return $this->choices;
    }

    public function isSelected($data, $value)
    {
        if (is_array($data['value'])) {
            return in_array($value, $data['value']);
        } else {
            return ($data['value'] == $value);
        }
    }

    public function getMultiple(): bool
    {
        return $this->multiple;
    }

    public function isAssoc(array $arr)
    {
        if (empty($arr)) {
            return false;
        }

        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
