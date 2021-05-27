<?php

namespace Lle\CruditBundle\Filter\FilterType;

use DateTime;
use Doctrine\ORM\QueryBuilder;

/**
 * DateFilterType
 */
class DateFilterType extends AbstractFilterType
{

    protected $yearRange;


    public function configure(array $config = [])
    {
        parent::configure($config);
        $this->yearRange = (isset($config['yearRange'])) ? $config['yearRange'] : null;
        $this->defaults['op'] = 'equal';
    }

    public function setDefaults($default)
    {
        parent::setDefaults($default);
        switch ($this->defaults['value']) {
            case 'now':
                $return = date('d/m/Y');
                break;
            default:
                $return = $default;
        }
        $this->defaults['value'] = $return;
    }



    /**
     * @param array  $data     The data
     * @param string $this->id The unique identifier
     */
    public function apply(QueryBuilder $queryBuilder): void
    {
        if (isset($this->data['value']) && $this->data['value'] && isset($this->data['op'])) {
            $date = DateTime::createFromFormat('d/m/Y', $this->data['value']);
            if (!$date) {
                return;
            }

            switch ($this->data['op']) {
                case 'equal':
                    $queryBuilder->andWhere($queryBuilder->expr()->like($this->alias . $this->columnName, ':var_' . $this->id));
                    break;
                case 'before':
                    $queryBuilder->andWhere($queryBuilder->expr()->lte($this->alias . $this->columnName, ':var_' . $this->id));
                    break;
                case 'after':
                    $queryBuilder->andWhere($queryBuilder->expr()->gt($this->alias . $this->columnName, ':var_' . $this->id));
                    break;
            }
                $queryBuilder->setParameter('var_' . $this->id, $date->format('Y-m-d') . '%');
        }
    }

    public function getDatePickerOptions()
    {
        $options = [];
        $options['changeMonth'] =  true;
        $options['changeYear'] = true;
        $options['dateFormat'] = 'dd/mm/yy';
        if ($this->yearRange) {
            $options['yearRange'] = $this->yearRange;
        }
        return $options;
    }

    public function getStateTemplate(): string
    {
        return '@LleCrudit/filter/state/date_filter.html.twig';
    }

    public function getTemplate(): string
    {
        return '@LleCrudit/filter/type/date_filter.html.twig';
    }
}
