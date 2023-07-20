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
    public function apply(QueryBuilder $queryBuilder): void
    {
        /**
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
