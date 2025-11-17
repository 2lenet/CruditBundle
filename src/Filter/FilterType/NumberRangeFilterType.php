<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Doctrine\ORM\QueryBuilder;
use Lle\CruditBundle\Contracts\FilterTypeInterface;

class NumberRangeFilterType extends AbstractFilterType
{
    public static function new(string $fieldname): self
    {
        $f = new self($fieldname);
        $f->setAdditionnalKeys(['to']);

        return $f;
    }

    public function apply(QueryBuilder $queryBuilder): void
    {
        if (!isset($this->data['op'])) {
            return;
        }

        $op = $this->data['op'];

        [$column, $alias, $paramname] = $this->getQueryParams($queryBuilder);

        $query = $this->getPattern($op, $column, $alias, $column, $paramname) ?? '';
        $this->applyAdditionnalFields($queryBuilder, $query, $op, $paramname);

        if (isset($this->data['value']) && $this->data['value'] !== '') {
            switch ($op) {
                case FilterTypeInterface::OPERATOR_IS_NULL:
                    $queryBuilder->andWhere($query);
                    break;
                case FilterTypeInterface::OPERATOR_INTERVAL:
                    if (!isset($this->data['to']) || $this->data['value'] !== $this->data['to']) {
                        $intervalQuery = $this->getPattern(
                            FilterTypeInterface::OPERATOR_GREATER_THAN_EQUAL,
                            $column,
                            $alias,
                            $column,
                            'min_' . $paramname
                        );

                        foreach ($this->additionnalFields as $additionnalField) {
                            [$additionnalColumn, $additionnalAlias] = $this->getQueryParams($queryBuilder, $additionnalField);
                            $intervalQuery .= ' OR ' . $this->getPattern(
                                FilterTypeInterface::OPERATOR_GREATER_THAN_EQUAL,
                                $additionnalColumn,
                                $additionnalAlias,
                                $additionnalColumn,
                                'min_' . $paramname
                            );
                        }

                        $queryBuilder->andWhere($intervalQuery);
                        $queryBuilder->setParameter('min_' . $paramname, $this->data['value']);
                    } else {
                        $intervalQuery = $this->getPattern(
                            FilterTypeInterface::OPERATOR_EQUAL,
                            $column,
                            $alias,
                            $column,
                            'min_' . $paramname
                        );

                        foreach ($this->additionnalFields as $additionnalField) {
                            [$additionnalColumn, $additionnalAlias] = $this->getQueryParams($queryBuilder, $additionnalField);
                            $intervalQuery .= ' OR ' . $this->getPattern(
                                FilterTypeInterface::OPERATOR_EQUAL,
                                $additionnalColumn,
                                $additionnalAlias,
                                $additionnalColumn,
                                'min_' . $paramname
                            );
                        }

                        $queryBuilder->andWhere($intervalQuery);
                        $queryBuilder->setParameter('min_' . $paramname, $this->data['value']);
                    }
                    break;
            }
        }

        if (isset($this->data['to']) && $this->data['to'] !== '') {
            switch ($op) {
                case FilterTypeInterface::OPERATOR_IS_NULL:
                    $queryBuilder->andWhere($query);
                    break;
                case FilterTypeInterface::OPERATOR_INTERVAL:
                    if (!isset($this->data['value']) || $this->data['value'] !== $this->data['to']) {
                        $intervalQuery = $this->getPattern(
                            FilterTypeInterface::OPERATOR_LESS_THAN_EQUAL,
                            $column,
                            $alias,
                            $column,
                            'max_' . $paramname
                        );

                        foreach ($this->additionnalFields as $additionnalField) {
                            [$additionnalColumn, $additionnalAlias] = $this->getQueryParams($queryBuilder, $additionnalField);
                            $intervalQuery .= ' OR ' . $this->getPattern(
                                FilterTypeInterface::OPERATOR_LESS_THAN_EQUAL,
                                $additionnalColumn,
                                $additionnalAlias,
                                $additionnalColumn,
                                'max_' . $paramname
                            );
                        }

                        $queryBuilder->andWhere($intervalQuery);
                        $queryBuilder->setParameter('max_' . $paramname, $this->data['to']);
                    }
                    break;
            }
        }

        $this->applyAdditionnalConditions($queryBuilder);
    }

    public function getOperators(): array
    {
        return [
            FilterTypeInterface::OPERATOR_INTERVAL => ['icon' => 'fas fa-arrows-alt-h'],
            FilterTypeInterface::OPERATOR_IS_NULL => ['icon' => 'far fa-square'],
        ];
    }
}
