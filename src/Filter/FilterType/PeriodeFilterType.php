<?php

namespace Lle\CruditBundle\Filter\FilterType;

use DateTime;
use Doctrine\ORM\QueryBuilder;

/**
 * DateFilterType
 */
class PeriodeFilterType extends AbstractFilterType
{
    /**
     * @param array  $data     The data
     * @param string $id The unique identifier
     */
    public function apply(QueryBuilder $queryBuilder): void
    {
        /*
        if (isset($this->data['value'])) {
            $qb = $queryBuilder;
            $from = $to = null;
            $c = $this->alias . $this->columnName;
            if (isset($this->data['value']['from']) && $this->data['value']['from']) {
                $from = DateTime::createFromFormat($this->format, $this->data['value']['from']);
                if ($from) {
                    $from = $from->format('Y-m-d');
                    $qb->andWhere($c . ' >= :var_from_' . $this->id);
                    $queryBuilder->setParameter('var_from_' . $this->id, $from);
                }
            }
            if (isset($this->data['value']['to']) && $this->data['value']['to']) {
                $to = DateTime::createFromFormat($this->format, $this->data['value']['to']);
                if ($to) {
                    $to->modify('+1 day');
                    $to = $to->format('Y-m-d');
                    $qb->andWhere($c . ' < :var_to_' . $this->id);
                    $queryBuilder->setParameter('var_to_' . $this->id, $to);
                }
            }
        }
        */
    }

    public function getStateTemplate(): string
    {
        return '@LleCrudit/filter/state/periode_filter.html.twig';
    }

    public function getTemplate(): string
    {
        return '@LleCrudit/filter/type/periode_filter.html.twig';
    }
}
