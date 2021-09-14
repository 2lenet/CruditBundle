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
        $arr = explode(':',$this->id);
        if (count($arr)>1) {
            $id = $arr[1];
            $alias = $arr[0].'.';
            if (!in_array($arr[0],$queryBuilder->getAllAliases())) {
                $queryBuilder->join($this->alias . $arr[0], $arr[0]);
            }
        } else {
            $id = $this->id;
            $alias = $this->alias;
        }

        $query = $this->getPattern($op, $id, $alias, $id);

        if (in_array($op, ["isnull", "isnotnull"])) {
            $queryBuilder->andWhere($query);
        } else if (
            isset($this->data['value'])
            && $this->data['value']
        ) {
            $value = trim($this->data["value"]);

            // SET QUERY PARAMETERS
            switch ($op) {
                case 'contains':
                case 'doesnotcontain':
                    $queryBuilder->setParameter("val_" . $id, "%" . $value . "%");
                    break;
                case 'startswith':
                    $queryBuilder->setParameter("val_" . $id, $value . "%");
                    break;
                case 'endswith':
                    $queryBuilder->setParameter("val_" . $id, "%" . $value);
                    break;
                case 'eq':
                case 'neq':
                    $queryBuilder->setParameter("val_" . $id, $value);
            }

            $queryBuilder->andWhere($query);
        }
    }


    /**
     * @param string op the op to use
     * @param string parameter the query parameter to set
     * @return string
     */
    private function getPattern($op, $id, $alias, $col)
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
                $pattern = $alias . $col . ' = :val_' . $id;
                break;
            case "neq":
                $pattern = $alias . $col . ' != :val_' . $id;
                break;
            case "contains":
            case "endswith":
            case "startswith":
                $pattern = $alias . $col . ' LIKE :val_' . $id;
                break;
            case "doesnotcontain":
                $pattern = $alias . $col . ' NOT LIKE :val_' . $id;
        }

        return $pattern ? "(" . $pattern . ")" : null;
    }
}
