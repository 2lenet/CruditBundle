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

        // MAKE QUERY
        $query = $this->getPattern($op, $this->id, $this->alias, $this->columnName);
        /*foreach ($this->additionalProperties as $additionalCol) {
            if (strpos($additionalCol, '.') !== false) {
                $alias = '';
            } else {
                $alias = $this->alias;
            }

            $pattern = $this->getPattern($op, $this->id, $alias, $additionalCol);

            if ($pattern) {
                $query .= " OR " . $pattern;
            }
        }*/

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
                    $queryBuilder->setParameter("val_" . $this->id, "%" . $value . "%");
                    break;
                case 'startswith':
                    $queryBuilder->setParameter("val_" . $this->id, $value . "%");
                    break;
                case 'endswith':
                    $queryBuilder->setParameter("val_" . $this->id, "%" . $value);
                    break;
                case 'equals':
                case 'notequals':
                    $queryBuilder->setParameter("val_" . $this->id, $value);
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
            case "equals":
                $pattern = $alias . $col . ' = :val_' . $id;
                break;
            case "notequals":
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
