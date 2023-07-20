<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Doctrine\ORM\QueryBuilder;

/**
 * BooleanFilterType
 *
 * For boolean values.
 */
class BooleanFilterType extends AbstractFilterType
{
    public static function new(string $fieldname): self
    {
        return new self($fieldname);
    }

    public function getOperators(): array
    {
        return [
            "eq" => ["icon" => "fas fa-equals"],
            "neq" => ["icon" => "fas fa-not-equal"],
        ];
    }

    public function apply(QueryBuilder $queryBuilder): void
    {
        [$column, $alias, $params] = $this->getQueryParams($queryBuilder);

        if (isset($this->data['value']) && $this->data['value'] && isset($this->data['op'])) {
            if ($this->data['op'] == 'eq' && $this->data['value'] !== 'all') {
                switch ($this->data['value']) {
                    case 'true':
                        $queryBuilder->andWhere($queryBuilder->expr()->eq($alias . $column, 'true'));
                        break;
                    case 'false':
                        $queryBuilder->andWhere($queryBuilder->expr()->eq($alias . $column, 'false'))
                            ->andWhere($queryBuilder->expr()->isNotNull($alias . $column));
                        break;
                }
            } elseif ($this->data['op'] == 'neq') {
                switch ($this->data['value']) {
                    case 'true':
                        $queryBuilder->andWhere($queryBuilder->expr()->eq($alias . $column, 'false'))
                            ->andWhere($queryBuilder->expr()->isNotNull($alias . $column));
                        break;
                    case 'false':
                        $queryBuilder->andWhere($queryBuilder->expr()->eq($alias . $column, 'true'));
                        break;
                    case 'all':
                        $queryBuilder->andWhere($queryBuilder->expr()->isNull($alias . $column));
                        break;
                }
            }
        }
    }

    public function isSelected(array $data, string $value): bool
    {
        if (is_array($data)) {
            if (array_key_exists('value', $data) && $data["value"] === $value) {
                return true;
            }
        }

        return false;
    }
}
