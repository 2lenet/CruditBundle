<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Doctrine\ORM\QueryBuilder;

/**
 * TreeFilterType
 *
 * For Gedmo trees.
 */
class TreeFilterType extends EntityFilterType
{
    public static function new(
        $fieldname,
        $entityClass
    ): self
    {
        $f = new self($fieldname);
        $f->setAdditionnalKeys(["items"]);
        $f->setEntityClass($entityClass);

        return $f;
    }

    public function apply(QueryBuilder $queryBuilder): void
    {
        /**
         * TODO : make this work for trees
         *
         * We either need entity manager injection (then we can use Gedmo's children() method)
         * or a nice querybuilder that can handle multiple entities without duplicates
         */

        parent::apply($queryBuilder);
    }

    public function getStateTemplate(): string
    {
        return '@LleCrudit/filter/state/entity_filter.html.twig';
    }

    public function getTemplate(): string
    {
        return '@LleCrudit/filter/type/entity_filter.html.twig';
    }
}
