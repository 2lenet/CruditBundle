<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Doctrine\ORM\QueryBuilder;
use Lle\CruditBundle\Contracts\FilterTypeInterface;

/**
 * StringFilterType
 *
 * For strings.
 */
class StringFilterType extends AbstractFilterType
{
    protected array $additionnalFields = [];

    public static function new(string $fieldname): self
    {
        return new self($fieldname);
    }

    public function getAdditionnalFields(): array
    {
        return $this->additionnalFields;
    }

    public function setAdditionnalFields(array $additionnalFields): static
    {
        $this->additionnalFields = $additionnalFields;

        return $this;
    }

    public function getOperators(): array
    {
        return [
            FilterTypeInterface::OPERATOR_CONTAINS => ["icon" => "fa fa-text-width"],
            FilterTypeInterface::OPERATOR_STARTS_WITH => ["icon" => "far fa-caret-square-right"],
            FilterTypeInterface::OPERATOR_ENDS_WITH => ["icon" => "far fa-caret-square-left"],
            FilterTypeInterface::OPERATOR_EQUAL => ["icon" => "fas fa-equals"],
            FilterTypeInterface::OPERATOR_NOT_EQUAL => ["icon" => "fas fa-not-equal"],
            FilterTypeInterface::OPERATOR_IS_NULL => ["icon" => "far fa-square"],
            FilterTypeInterface::OPERATOR_IS_NOT_NULL => ["icon" => "fas fa-square"],
        ];
    }

    public function apply(QueryBuilder $queryBuilder): void
    {
        if (!isset($this->data["op"])) {
            return;
        }

        $op = $this->data["op"];

        // ADD JOIN IF NEEDED
        [$column, $alias, $paramname] = $this->getQueryParams($queryBuilder);

        $query = $this->getPattern($op, $column, $alias, $column, $paramname);

        foreach ($this->additionnalFields as $additionnalField) {
            [$additionnalColumn, $additionnalAlias] = $this->getQueryParams($queryBuilder, $additionnalField);
            $query .= ' OR ' . $this->getPattern(
                $op,
                $additionnalColumn,
                $additionnalAlias,
                $additionnalColumn,
                $paramname
            );
        }

        if (in_array($op, [FilterTypeInterface::OPERATOR_IS_NULL, FilterTypeInterface::OPERATOR_IS_NOT_NULL])) {
            $queryBuilder->andWhere($query);
        } elseif (
            isset($this->data['value'])
            && $this->data['value']
        ) {
            $value = trim($this->data["value"]);

            // SET QUERY PARAMETERS
            switch ($op) {
                case FilterTypeInterface::OPERATOR_CONTAINS:
                case FilterTypeInterface::OPERATOR_DOES_NOT_CONTAIN:
                    $queryBuilder->setParameter($paramname, "%" . $value . "%");
                    break;
                case FilterTypeInterface::OPERATOR_STARTS_WITH:
                    $queryBuilder->setParameter($paramname, $value . "%");
                    break;
                case FilterTypeInterface::OPERATOR_ENDS_WITH:
                    $queryBuilder->setParameter($paramname, "%" . $value);
                    break;
                case FilterTypeInterface::OPERATOR_EQUAL:
                case FilterTypeInterface::OPERATOR_NOT_EQUAL:
                    $queryBuilder->setParameter($paramname, $value);
            }

            $queryBuilder->andWhere($query);
        }
    }

    private function getPattern(string $op, string $id, string $alias, string $col, string $paramname): ?string
    {
        $pattern = null;
        switch ($op) {
            case FilterTypeInterface::OPERATOR_IS_NULL:
                $pattern = $alias . $col . ' IS NULL OR ' . $alias . $col . " = '' ";
                break;
            case FilterTypeInterface::OPERATOR_IS_NOT_NULL:
                $pattern = $alias . $col . ' IS NOT NULL AND ' . $alias . $col . " <> '' ";
                break;
            case FilterTypeInterface::OPERATOR_EQUAL:
                $pattern = $alias . $col . ' = :' . $paramname;
                break;
            case FilterTypeInterface::OPERATOR_NOT_EQUAL:
                $pattern = $alias . $col . ' != :' . $paramname;
                break;
            case FilterTypeInterface::OPERATOR_CONTAINS:
            case FilterTypeInterface::OPERATOR_ENDS_WITH:
            case FilterTypeInterface::OPERATOR_STARTS_WITH:
                $pattern = $alias . $col . ' LIKE :' . $paramname;
                break;
            case FilterTypeInterface::OPERATOR_DOES_NOT_CONTAIN:
                $pattern = $alias . $col . ' NOT LIKE :' . $paramname;
        }

        return $pattern ? "(" . $pattern . ")" : null;
    }
}
