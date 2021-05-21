<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Doctrine\ORM\EntityManagerInterface;
use Lle\CruditBundle\Filter\HiddenEntity;

class EntityFilterType extends AbstractFilterType
{

    protected $table;
    protected $method;
    protected $multiple;
    protected $args;
    protected $group_by;
    protected $method_label;
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function configure(array $config = [])
    {
        parent::configure($config);
        $this->table = $config['table'];
        $this->method = $config['method'] ?? 'findAll';
        $this->method_label = $config['method_label'] ?? '__toString';
        $this->args = $config['arguments'] ?? null;
        $this->multiple = $config['multiple'] ?? true;
        $this->group_by = $config['group_by'] ?? null;
    }

    public function apply($queryBuilder)
    {
        if (isset($this->data['value'])) {
            if ($this->getMultiple()) {
                $queryBuilder->andWhere($queryBuilder->expr()->in($this->alias . $this->columnName, ':var_' . $this->uniqueId));
            } else {
                $queryBuilder->andWhere($queryBuilder->expr()->eq($this->alias . $this->columnName, ':var_' . $this->uniqueId));
            }

            $queryBuilder->setParameter('var_' . $this->uniqueId, $this->data['value']);
        }
    }

    public function getEntities($data)
    {
        if ($this->isHidden()) { //si le filtre est hidden pas de requet. héééé ouai
            $elements = array();
            if (is_array($data['value'])) {
                foreach ($data['value'] as $value) {
                    $elements[] = new HiddenEntity($value);
                }
            } else {
                $elements[] = new HiddenEntity($data['value']);
            }
        } else {
            $m = $this->method;
            $args = $this->args;
            $repo = $this->em->getRepository($this->table);
            if ($args) {
                $classRfx = new \ReflectionClass(get_class($repo));
                $methodRfx = $classRfx->getMethod($m);
                $elements = $methodRfx->invokeArgs($repo, $args);
            } else {
                $elements = $repo->$m();
            }
        }
        return $elements;
    }
    public function getItemLabel($id)
    {
        $repo = $this->em->getRepository($this->table);
        $obj = $repo->find($id);
        return (string) $obj;
    }

    public function isSelected($data, $entity)
    {
        if ($this->getMultiple() and is_array($data['value'])) {
            return in_array($entity->getId(), $data['value']);
        } else {
            return ($data && $data['value'] == $entity->getId());
        }
    }

    public function getMultiple()
    {
        return $this->multiple;
    }

    public function getGroupBy()
    {
        return $this->group_by;
    }

    public function getLabelForOptGroup($entity)
    {

        $groupBy = $this->getGroupBy();
        $method = "get" . ucfirst($groupBy);

        return call_user_func(array($entity, $method));
    }

    public function getLabel($entity)
    {

        return call_user_func(array($entity, $this->method_label));
    }

    public function getStateTemplate()
    {
        return '@LleCrudit/filter/state/entity_filter.html.twig';
    }

    public function getTemplate()
    {
        return '@LleCrudit/filter/type/entity_filter.html.twig';
    }
}
