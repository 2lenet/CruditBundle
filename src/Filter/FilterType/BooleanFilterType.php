<?php

namespace Lle\CruditBundle\Filter\FilterType;

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

    public function apply($queryBuilder): void
    {
        if (isset($this->data['value']) && $this->data['value']) {
            $value = $this->data['value'];
            if ($value != 'all') {
                switch ($value) {
                    case 'true':
                        $queryBuilder->andWhere($queryBuilder->expr()->eq($this->alias . $this->columnName, 'true'));
                        break;
                    case 'false':
                        $queryBuilder->andWhere($queryBuilder->expr()->eq($this->alias . $this->columnName, 'false'))
                            ->andWhere($queryBuilder->expr()->isNotNull($this->alias . $this->columnName));
                        break;
                }
            }
        }
    }

    public function isSelected($data, $value)
    {
        if (is_array($data)) {
            if (array_key_exists('value', $data) && $data["value"] === $value) {
                return true;
            }
        }
        return false;
    }
}
