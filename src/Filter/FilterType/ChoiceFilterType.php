<?php

namespace Lle\CruditBundle\Filter\FilterType;

/**
 * Class ChoiceFilterType
 * @package Lle\CruditBundle\Filter\FilterType
 */
class ChoiceFilterType extends AbstractFilterType
{

    private $choices;
    private $multiple;

    public function __construct($fieldname, array $choices, bool $isMultiple = false)
    {
        parent::__construct($fieldname);
        $this->setChoices($choices);
        $this->setMultiple($isMultiple);
    }

    /**
     * @param string $fieldname
     * @param array $choices
     * @param bool $isMultiple
     * @return ChoiceFilterType
     */
    public static function new(string $fieldname, array $choices, bool $isMultiple = false): ChoiceFilterType
    {
        return new self($fieldname, $choices, $isMultiple);
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

    public function apply($queryBuilder)
    {
        if (isset($this->data['value'])) {
            $qb = $queryBuilder;
            if ($this->getMultiple()) {
                $queryBuilder->andWhere($queryBuilder->expr()->in($this->alias . $this->columnName, ':var_' . $this->id));
            } else {
                $queryBuilder->andWhere($queryBuilder->expr()->eq($this->alias . $this->columnName, ':var_' . $this->id));
            }
            $queryBuilder->setParameter('var_' . $this->id, $this->data['value']);
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
        if (array() === $arr) {
            return false;
        }
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    public function getStateTemplate()
    {
        return '@LleCrudit/filter/state/choice_filter.html.twig';
    }

    public function getTemplate()
    {
        return '@LleCrudit/filter/type/choice_filter.html.twig';
    }
}
