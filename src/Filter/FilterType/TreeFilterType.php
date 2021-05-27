<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Doctrine\ORM\QueryBuilder;

/**
 * StringFilterType
 */
class TreeFilterType extends EntityFilterType
{
    protected $startLevel;

    /**
     * @param array  $data     The data
     * @param string $id The unique identifier
     */
    public function apply(QueryBuilder $queryBuiler): void
    {
        /*
        if (isset($data['value'])) {
            if ($this->getMultiple()) {
                $nodes = $this->em->getRepository($this->table)->findById($data['value']);
                $ids = array();
                foreach ($nodes as $node) {
                    $ids[] = $node;
                    $children = $this->em->getRepository($this->table)->children($node, false);
                    foreach ($children as $child) {
                        $ids[] = $child->getId();
                    }
                }
            } else {
                $node = $this->em->getRepository($this->table)->find($data['value']);
                $children = $this->em->getRepository($this->table)->children($node, false);
                $ids = array($node);
                foreach ($children as $child) {
                    $ids[] = $child->getId();
                }
            }
            $queryBuilder->andWhere($queryBuilder->expr()->in($alias . $col, $ids));
        }
        */
    }

    public function display($entity)
    {
        return ($entity->getLvl() >= $this->startLevel);
    }

    public function getTemplate(): string
    {
        return '@LleCrudit/filter/type/tree_filter.html.twig';
    }
}
