<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Doctrine\ORM\QueryBuilder;

/**
 * StringFilterType
 *
 * For strings.
 */
class StringFilterType extends AbstractFilterType
{
    public static function new(string $fieldname): self
    {
        return new self($fieldname);
    }

    public function getOperators(): array
    {
        return [
            "startswith" => ["icon" => "far fa-caret-square-right"],
            "contains" => ["icon" => "fa fa-text-width"],
            "endswith" => ["icon" => "far fa-caret-square-left"],
            "eq" => ["icon" => "fas fa-equals"],
            "neq" => ["icon" => "fas fa-not-equal"],
            "isnull" => ["icon" => "far fa-square"],
            "isnotnull" => ["icon" => "fas fa-square"],
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

        if (in_array($op, ["isnull", "isnotnull"])) {
            $queryBuilder->andWhere($query);
        } elseif (
            isset($this->data['value'])
            && $this->data['value']
        ) {
            $value = trim($this->data["value"]);

            // SET QUERY PARAMETERS
            switch ($op) {
                case 'contains':
                case 'doesnotcontain':
                    $queryBuilder->setParameter($paramname, "%" . $value . "%");
                    break;
                case 'startswith':
                    $queryBuilder->setParameter($paramname, $value . "%");
                    break;
                case 'endswith':
                    $queryBuilder->setParameter($paramname, "%" . $value);
                    break;
                case 'eq':
                case 'neq':
                    $queryBuilder->setParameter($paramname, $value);
            }

            $queryBuilder->andWhere($query);
        }
    }

    /**
     * @param string op the op to use
     * @param string parameter the query parameter to set
     * @return string
     */
    private function getPattern($op, $id, $alias, $col, $paramname)
    {
        $pattern = null;
        switch ($op) {
            case "isnull":
                $pattern = $alias . $col . ' IS NULL OR ' . $alias . $col . " = '' ";
                break;
            case "isnotnull":
                $pattern = $alias . $col . ' IS NOT NULL AND ' . $alias . $col . " <> '' ";
                break;
            case "eq":
                $pattern = $alias . $col . ' = :' . $paramname;
                break;
            case "neq":
                $pattern = $alias . $col . ' != :' . $paramname;
                break;
            case "contains":
            case "endswith":
            case "startswith":
                $pattern = $alias . $col . ' LIKE :' . $paramname;
                break;
            case "doesnotcontain":
                $pattern = $alias . $col . ' NOT LIKE :' . $paramname;
        }

        return $pattern ? "(" . $pattern . ")" : null;
    }
}
