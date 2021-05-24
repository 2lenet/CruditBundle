<?php

namespace Lle\CruditBundle\Filter\FilterType;

/**
 * StringFilterType
 */
class StringFilterType extends AbstractFilterType
{
    /**
     * @var string
     */
    private $defaultValue;

    public static function new(string $fieldname): self
    {
        return new self($fieldname);
    }

    public function getOperators()
    {
        return [
            "startswith" => ["icon" => "far fa-caret-square-right"],
            "contains" => ["icon" => "fa fa-text-width"],
            "endswith" => ["icon" => "far fa-caret-square-left"],
            "isnull" => ["icon" => "far fa-square"],
            "isnotnull" => ["icon" => "fas fa-square"],
        ];
    }

    public function apply($queryBuilder)
    {
        if (isset($this->data['value']) && $this->data['value']) {
            $value = trim($this->data["value"]);
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
            $queryBuilder->andWhere($query);

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
        }
    }


    /**
     * @return string
     * @param string op the op to use
     * @param string parameter the query parameter to set
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

    public function getStateTemplate()
    {
        return '@LleCrudit/filter/state/string_filter.html.twig';
    }

    public function getTemplate()
    {
        return '@LleCrudit/filter/type/string_filter.html.twig';
    }
}
