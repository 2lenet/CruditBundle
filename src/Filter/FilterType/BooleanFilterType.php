<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Doctrine\ORM\QueryBuilder;
use Lle\CruditBundle\Contracts\FilterTypeInterface;

class BooleanFilterType extends AbstractFilterType
{
    public static function new(string $fieldname): self
    {
        return new self($fieldname);
    }

    public function getOperators(): array
    {
        return [
            FilterTypeInterface::OPERATOR_EQUAL => ['icon' => 'fas fa-equals'],
            FilterTypeInterface::OPERATOR_NOT_EQUAL => ['icon' => 'fas fa-not-equal'],
        ];
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

        if (isset($this->data['value']) && $this->data['value']) {
            $value = $this->data['value'];

            if ($op === FilterTypeInterface::OPERATOR_NOT_EQUAL && $value === 'all') {
                $additionnalQuery = '(' . $alias . $column . ' IS NULL)';

                foreach ($this->additionnalFields as $additionnalField) {
                    [$additionnalColumn, $additionnalAlias] = $this->getQueryParams($queryBuilder, $additionnalField);
                    $additionnalQuery .= ' OR (' . $additionnalAlias . $additionnalColumn . ' IS NULL)';
                }

                $queryBuilder->andWhere($additionnalQuery);
            } elseif ($value !== 'all') {
                $queryBuilder->andWhere($query);
                $queryBuilder->setParameter($paramname, $value === 'true' ? true : false);
            }
        }

        $this->applyAdditionnalConditions($queryBuilder);
    }

    public function isSelected(?array $data, string $value): bool
    {
        if (is_array($data)) {
            if (array_key_exists('value', $data) && $data['value'] === $value) {
                return true;
            }
        }

        return false;
    }
}
