<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Doctrine\ORM\QueryBuilder;
use Lle\CruditBundle\Contracts\FilterTypeInterface;

/**
 * ChoiceFilterType
 *
 * For predefined select fields.
 */
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
            FilterTypeInterface::OPERATOR_EQUAL => ['icon' => 'fas fa-equals'],
            FilterTypeInterface::OPERATOR_NOT_EQUAL => ['icon' => 'fas fa-not-equal'],
            FilterTypeInterface::OPERATOR_IS_NULL => ["icon" => "far fa-square"],
            FilterTypeInterface::OPERATOR_IS_NOT_NULL => ["icon" => "fas fa-square"],
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
        [$column, $alias, $paramname] = $this->getQueryParams($queryBuilder);

        $data = [];
        if (isset($this->data['value']) && $this->data['value'] != '') {
            $data = explode(',', $this->data['value']);
        }


        if (isset($this->data["op"])) {
            switch ($this->data['op']) {
                case FilterTypeInterface::OPERATOR_IS_NULL:
                    $queryBuilder->andWhere($alias . $column . ' IS NULL OR ' . $alias . $column . " = '' ");
                    break;
                case FilterTypeInterface::OPERATOR_IS_NOT_NULL:
                    $queryBuilder->andWhere($alias . $column . ' IS NOT NULL OR ' . $alias . $column . " = '' ");
                    break;
                case FilterTypeInterface::OPERATOR_NOT_EQUAL:
                    if (!empty($data)) {
                        $queryBuilder->andWhere($queryBuilder->expr()->notIn($alias . $column, ':' . $paramname));
                        $queryBuilder->setParameter($paramname, $data);
                    }
                    break;
                case FilterTypeInterface::OPERATOR_EQUAL:
                default:
                    if (!empty($data)) {
                        $queryBuilder->andWhere($queryBuilder->expr()->in($alias . $column, ':' . $paramname));
                        $queryBuilder->setParameter($paramname, $data);
                    }
            }
        }
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
