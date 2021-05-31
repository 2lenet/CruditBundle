<?php

namespace Lle\CruditBundle\Filter\FilterType;

/**
 * BooleanFilterType
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
                            ->andWhere($queryBuilder->expr()->isNotNull($this->alias . $this->columnName))
                        ;
                        break;
                }
            }
        }
    }

    public function isSelected($data, $value)
    {
        return is_array($data) ? $data["value"] === $value : false;
    }

    public function getStateTemplate(): string
    {
        return '@LleCrudit/filter/state/boolean_filter.html.twig';
    }

    public function getTemplate(): string
    {
        return '@LleCrudit/filter/type/boolean_filter.html.twig';
    }
}
