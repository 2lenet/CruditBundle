<?php

namespace Lle\CruditBundle\Filter\FilterType;

use DateTime;
use Doctrine\ORM\QueryBuilder;

/**
 * DateTimeFilterType
 */
class DateTimeFilterType extends AbstractFilterType
{

    /**
     * @param array  $data     The data
     * @param string $this->id The unique identifier
     */
    public function apply(QueryBuilder $queryBuilder): void
    {
        if (isset($data['value']) && isset($data['op'])) {
            /** @var DateTime $datetime */
            [$date, $time] = [null,null];
            if (strstr($data['value']['date'], ' ')) {
                [$date, $time] = explode(' ', $data['value']['date']);
            }
            $date = empty($date) ? date('d/m/Y') : $date;
            $time = empty($time) ? date('H:i')   : $time;
            $datetime = DateTime::createFromFormat('d/m/Y H:i', $date . ' ' . $time);
            switch ($data['op']) {
                case 'before':
                    $this->queryBuilder->andWhere($queryBuilder->expr()->lte($alias . $col, ':var_' . $this->id));
                    break;
                case 'after':
                    $this->queryBuilder->andWhere($queryBuilder->expr()->gt($alias . $col, ':var_' . $this->id));
                    break;
                case 'equal':
                    $this->queryBuilder->andWhere($alias . $this->columnName . ' = :var_' . $this->id);
                    break;
            }
            $this->queryBuilder->setParameter('var_' . $this->id, $datetime);
        }
    }

    public function getStateTemplate(): string
    {
        return '@LleCrudit/filter/state/date_time_filter.html.twig';
    }

    public function getTemplate(): string
    {
        return '@LleCrudit/filter/type/date_time_filter.html.twig';
    }
}
