<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Workflow\Registry;

/**
 * StringFilterType
 */
class WorkflowFilterType extends ChoiceFilterType
{

    private $choices;
    private $excludes;
    private $multiple;
    private $registry;
    private $em;


    public function __construct(EntityManagerInterface $em, Registry $registry)
    {
        $this->em = $em;
        $this->registry = $registry;
    }

    /**
     * @param string $columnName The column name
     * @param string $alias      The alias
     */
    public function configure(array $config = [])
    {
        $config['choices'] = $config['choices'] ?? $this->registry->get(
            $this->em->getClassMetadata($config['class'] ?? $config['data_class'])->newInstance(),
            $config['name'] ?? null
        )->getDefinition()->getPlaces();
        parent::configure($config);

        $this->excludes = $config['excludes'] ?? [];
        $this->multiple = $config['multiple'] ?? true;
    }


    public function apply(QueryBuilder $queryBuilder): void
    {
        if (isset($this->data['value'])) {
            if ($this->getMultiple()) {
                $queryBuilder->andWhere($queryBuilder->expr()->in($this->alias . $this->columnName, ':var_' . $this->id));
            } else {
                $queryBuilder->andWhere($queryBuilder->expr()->eq($this->alias . $this->columnName, ':var_' . $this->id));
            }
            $queryBuilder->setParameter('var_' . $this->id, $this->data['value']);
        } elseif (!empty($this->excludes)) {
            $queryBuilder->andWhere($queryBuilder->expr()->notin($this->alias . $this->columnName, ':var_' . $this->id));
            $queryBuilder->setParameter('var_' . $this->id, $this->excludes);
        }
    }


    public function isSelected($data, $value)
    {
        if (is_null($data['value'])) {
            if (count($this->excludes)) {
                return !in_array($value, $this->excludes);
            } else {
                return false;
            }
        }
        if (is_array($data['value'])) {
            return in_array($value, $data['value']);
        } else {
            return ($data['value'] == $value);
        }
    }

    public function getStateTemplate(): string
    {
        return '@LleCrudit/filter/state/workflow_filter.html.twig';
    }

    public function getTemplate(): string
    {
        return '@LleCrudit/filter/type/workflow_filter.html.twig';
    }
}
