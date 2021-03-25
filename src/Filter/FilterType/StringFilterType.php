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

    /**
     * @var string
     */
    private $defaultComparator;

    /**
     * @var array
     */
    private $additionalProperties;

    /**
     * @param string $columnName The column name
     * @param string $alias      The alias
     */
    public function configure(array $config = [])
    {
        parent::configure($config);
        $this->defaults = [
            'value' => $config['defaultValue'] ?? "",
            'comparator' => $config['defaultComparator'] ?? "contains"
        ];
        $this->additionalProperties = $config['additionalProperties'] ?? [];

        // must be an array
        if (!is_array($this->additionalProperties)) {
            $this->additionalProperties = [];
        }
    }


    public function apply($queryBuilder)
    {
        if (isset($this->data['value']) && $this->data['value']) {
            $value = trim($this->data["value"]);
            $comparator = $this->data["comparator"];

            // MAKE QUERY
            $query = $this->getPattern($comparator, $this->uniqueId, $this->alias, $this->columnName);
            foreach ($this->additionalProperties as $additionalCol) {
                if (strpos($additionalCol, '.') !== false) {
                    $alias = '';
                } else {
                    $alias = $this->alias;
                }

                $pattern = $this->getPattern($comparator, $this->uniqueId, $alias, $additionalCol);

                if ($pattern) {
                    $query .= " OR " . $pattern;
                }
            }
            $queryBuilder->andWhere($query);

            // SET QUERY PARAMETERS
            switch ($comparator) {
                case 'contains':
                case 'doesnotcontain':
                    $queryBuilder->setParameter("val_" . $this->uniqueId, "%" . $value . "%");
                    break;
                case 'startswith':
                    $queryBuilder->setParameter("val_" . $this->uniqueId, $value . "%");
                    break;
                case 'endswith':
                    $queryBuilder->setParameter("val_" . $this->uniqueId, "%" . $value);
                    break;
                case 'equals':
                case 'notequals':
                    $queryBuilder->setParameter("val_" . $this->uniqueId, $value);
            }
        }
    }


    /**
     * @return string
     * @param string comparator the comparator to use
     * @param string parameter the query parameter to set
     */
    private function getPattern($comparator, $uniqueId, $alias, $col)
    {
        $pattern = null;
        switch ($comparator) {
            case "isnull":
                $pattern = $alias . $col . ' IS NULL OR ' . $alias . $col . " = '' ";
                break;
            case "isnotnull":
                $pattern = $alias . $col . ' IS NOT NULL AND ' . $alias . $col . " <> '' ";
                break;
            case "equals":
                $pattern = $alias . $col . ' = :val_' . $uniqueId;
                break;
            case "notequals":
                $pattern = $alias . $col . ' != :val_' . $uniqueId;
                break;
            case "contains":
            case "endswith":
            case "startswith":
                $pattern = $alias . $col . ' LIKE :val_' . $uniqueId;
                break;
            case "doesnotcontain":
                $pattern = $alias . $col . ' NOT LIKE :val_' . $uniqueId;
        }

        return $pattern ? "(" . $pattern . ")" : null;
    }

    public function getStateTemplate()
    {
        return '@LleEasyAdminPlus/filter/state/string_filter.html.twig';
    }

    public function getTemplate()
    {
        return '@LleEasyAdminPlus/filter/type/string_filter.html.twig';
    }
}
