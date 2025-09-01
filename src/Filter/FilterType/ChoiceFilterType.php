<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Doctrine\ORM\QueryBuilder;
use Lle\CruditBundle\Contracts\FilterTypeInterface;

class ChoiceFilterType extends AbstractFilterType
{
    protected array $choices;
    protected bool $multiple;

    public static function new(string $fieldname, array $choices, bool $isMultiple = false): ChoiceFilterType
    {
        $f = new self($fieldname);
        $f->setChoices($choices);
        $f->setMultiple($isMultiple);

        return $f;
    }

    public function getOperators(): array
    {
        return [
            FilterTypeInterface::OPERATOR_IN => ['icon' => 'fas fa-equals'],
            FilterTypeInterface::OPERATOR_NOT_IN => ['icon' => 'fas fa-not-equal'],
            FilterTypeInterface::OPERATOR_IS_NULL => ['icon' => 'far fa-square'],
            FilterTypeInterface::OPERATOR_IS_NOT_NULL => ['icon' => 'fas fa-square'],
        ];
    }

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

    public function setMultiple(bool $isMultiple): void
    {
        $this->multiple = $isMultiple;
    }

    public function apply(QueryBuilder $queryBuilder): void
    {
        if (!isset($this->data['op'])) {
            return;
        }

        $op = $this->data['op'];

        [$column, $alias, $paramname] = $this->getQueryParams($queryBuilder);

        $query = $this->getPattern($op, $column, $alias, $column, $paramname);
        $this->applyAdditionnalFields($queryBuilder, $query, $op, $paramname);

        if (in_array($op, [FilterTypeInterface::OPERATOR_IS_NULL, FilterTypeInterface::OPERATOR_IS_NOT_NULL])) {
            $queryBuilder->andWhere($query);
        } elseif (isset($this->data['value']) && $this->data['value'] !== '') {
            $value = explode(',', $this->data['value']);

            $queryBuilder->andWhere($query);
            $queryBuilder->setParameter($paramname, $value);
        }

        $this->applyAdditionnalConditions($queryBuilder);
    }

    public function getChoices(): array
    {
        return $this->choices;
    }

    public function isSelected(array $data, string $value): bool
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

    public function isAssoc(array $arr): bool
    {
        if (empty($arr)) {
            return false;
        }

        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
