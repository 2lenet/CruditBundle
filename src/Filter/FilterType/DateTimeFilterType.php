<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Doctrine\ORM\QueryBuilder;
use Lle\CruditBundle\Contracts\FilterTypeInterface;

class DateTimeFilterType extends AbstractFilterType
{
    public static function new(string $fieldname): self
    {
        $f = new self($fieldname);
        $f->setAdditionnalKeys(['time']);

        return $f;
    }

    /**
     * The date and time to compare with, as `Y-m-d H:i:s`.
     *
     * The field of the bar is a `datetime-local`: the value is `2026-11-05T10:30` (seconds optional) and `time` is empty.
     * The 1.x sent a date in `value` and the time in `time` (`10:30:00`), and a link or a session may still carry that:
     * `time` is read only when the value has no time of its own. No time at all is midnight.
     */
    public static function dateTime(string $value, string $time = ''): string
    {
        $value = trim(str_replace('T', ' ', $value));
        if (str_contains($value, ' ')) {
            [$value, $time] = explode(' ', $value, 2);
        }
        $time = trim($time);
        if ($time === '') {
            $time = '00:00:00';
        } elseif (substr_count($time, ':') === 1) {
            $time .= ':00';
        }

        return $value . ' ' . $time;
    }

    public function getOperators(): array
    {
        return [
            FilterTypeInterface::OPERATOR_EQUAL => ['icon' => 'fas fa-equals'],
            FilterTypeInterface::OPERATOR_BEFORE => ['icon' => 'fas fa-less-than'],
            FilterTypeInterface::OPERATOR_AFTER => ['icon' => 'fas fa-greater-than'],
        ];
    }

    public function apply(QueryBuilder $queryBuilder): void
    {
        if (!isset($this->data['op'])) {
            return;
        }

        $op = $this->data['op'];

        [$column, $alias, $paramname] = $this->getQueryParams($queryBuilder);

        $query = $this->getPattern($op, $column, $alias, $column, $paramname);
        $this->applyAdditionnalFields($queryBuilder, $query, $op, $paramname);

        if (isset($this->data['value']) && $this->data['value']) {
            $queryBuilder->andWhere($query);
            $queryBuilder->setParameter($paramname, self::dateTime((string) $this->data['value'], (string) ($this->data['time'] ?? '')));
        }

        $this->applyAdditionnalConditions($queryBuilder);
    }
}
