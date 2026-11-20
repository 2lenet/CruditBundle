<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Doctrine\ORM\Events;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Translatable\TranslatableListener;
use Lle\CruditBundle\Contracts\FilterTypeInterface;

class StringFilterType extends AbstractFilterType
{
    /**
     * Operators for which a row matches if the column OR one of its translations matches.
     */
    public const TRANSLATION_POSITIVE_OPERATORS = [
        FilterTypeInterface::OPERATOR_CONTAINS,
        FilterTypeInterface::OPERATOR_STARTS_WITH,
        FilterTypeInterface::OPERATOR_ENDS_WITH,
        FilterTypeInterface::OPERATOR_EQUAL,
    ];

    /**
     * Negative operator => positive operator used in the NOT EXISTS subquery:
     * a row matches if the column AND none of its translations match.
     */
    public const TRANSLATION_NEGATIVE_OPERATORS = [
        FilterTypeInterface::OPERATOR_NOT_EQUAL => FilterTypeInterface::OPERATOR_EQUAL,
        FilterTypeInterface::OPERATOR_DOES_NOT_CONTAIN => FilterTypeInterface::OPERATOR_CONTAINS,
    ];

    protected bool $searchInTranslations = false;

    /**
     * The TranslatableListener of each event manager (null: none), looked for once.
     *
     * @var \WeakMap<object, TranslatableListener|null>|null
     */
    private static ?\WeakMap $translatableListeners = null;

    public static function new(string $fieldname): self
    {
        return new self($fieldname);
    }

    public function getOperators(): array
    {
        return [
            FilterTypeInterface::OPERATOR_CONTAINS => ['icon' => 'fa fa-text-width'],
            FilterTypeInterface::OPERATOR_DOES_NOT_CONTAIN => ['icon' => 'fas fa-remove-format'],
            FilterTypeInterface::OPERATOR_STARTS_WITH => ['icon' => 'far fa-caret-square-right'],
            FilterTypeInterface::OPERATOR_ENDS_WITH => ['icon' => 'far fa-caret-square-left'],
            FilterTypeInterface::OPERATOR_EQUAL => ['icon' => 'fas fa-equals'],
            FilterTypeInterface::OPERATOR_NOT_EQUAL => ['icon' => 'fas fa-not-equal'],
            FilterTypeInterface::OPERATOR_IS_NULL => ['icon' => 'far fa-square'],
            FilterTypeInterface::OPERATOR_IS_NOT_NULL => ['icon' => 'fas fa-square'],
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
        $query = $this->addTranslationPattern(
            $queryBuilder,
            $query,
            $op,
            $this->columnName,
            $alias,
            $column,
            $paramname,
            $paramname
        );
        $this->applyAdditionnalFields($queryBuilder, $query, $op, $paramname);

        if (in_array($op, [FilterTypeInterface::OPERATOR_IS_NULL, FilterTypeInterface::OPERATOR_IS_NOT_NULL])) {
            $queryBuilder->andWhere($query);
        } elseif (isset($this->data['value']) && $this->data['value']) {
            $value = trim($this->data['value']);

            switch ($op) {
                case FilterTypeInterface::OPERATOR_CONTAINS:
                case FilterTypeInterface::OPERATOR_DOES_NOT_CONTAIN:
                    $queryBuilder->setParameter($paramname, '%' . $value . '%');
                    break;
                case FilterTypeInterface::OPERATOR_STARTS_WITH:
                    $queryBuilder->setParameter($paramname, $value . '%');
                    break;
                case FilterTypeInterface::OPERATOR_ENDS_WITH:
                    $queryBuilder->setParameter($paramname, '%' . $value);
                    break;
                case FilterTypeInterface::OPERATOR_EQUAL:
                case FilterTypeInterface::OPERATOR_NOT_EQUAL:
                    $queryBuilder->setParameter($paramname, $value);
                    break;
            }

            $queryBuilder->andWhere($query);
        }

        $this->applyAdditionnalConditions($queryBuilder);
    }

    /**
     * Set to true to also search in the Gedmo translations of the field (all locales).
     */
    public function setSearchInTranslations(bool $searchInTranslations): static
    {
        $this->searchInTranslations = $searchInTranslations;

        return $this;
    }

    public function isSearchInTranslations(): bool
    {
        return $this->searchInTranslations;
    }

    public function applyAdditionnalFields(
        QueryBuilder $queryBuilder,
        string &$query,
        string $op,
        string $paramname
    ): void {
        foreach ($this->additionnalFields as $additionnalField) {
            // the value is bound to the parameter of the filter; the field's own name gives its subquery alias
            [$additionnalColumn, $additionnalAlias, $additionnalParamname] = $this->getQueryParams(
                $queryBuilder,
                $additionnalField
            );
            $pattern = $this->getPattern($op, $additionnalColumn, $additionnalAlias, $additionnalColumn, $paramname);
            $query .= ' OR ' . $this->addTranslationPattern(
                $queryBuilder,
                $pattern,
                $op,
                $additionnalField,
                $additionnalAlias,
                $additionnalColumn,
                $paramname,
                $additionnalParamname
            );
        }
    }

    /**
     * If the search in translations is enabled and the field is translatable (Gedmo personal translation),
     * the search also looks into the field translations, in all locales:
     * - positive operators (contains, starts with, ends with, equal): column OR one translation matches;
     * - negative operators (not equal, does not contain): column AND no translation matches.
     * The id of the filter and $aliasKey (the name of the field's own parameter) give the alias of the subquery: unique
     * in the query, even when two filters search the same field, and the same for each request, so that the DQL stays
     * the same and the query cache of Doctrine is used.
     */
    protected function addTranslationPattern(
        QueryBuilder $queryBuilder,
        string $pattern,
        string $op,
        string $fieldPath,
        string $alias,
        string $col,
        string $paramname,
        string $aliasKey,
    ): string {
        if (!$this->searchInTranslations) {
            return $pattern;
        }

        if (in_array($op, self::TRANSLATION_POSITIVE_OPERATORS, true)) {
            $positiveOp = $op;
            $glue = ' OR EXISTS ';
        } elseif (isset(self::TRANSLATION_NEGATIVE_OPERATORS[$op])) {
            $positiveOp = self::TRANSLATION_NEGATIVE_OPERATORS[$op];
            $glue = ' AND NOT EXISTS ';
        } else {
            return $pattern;
        }

        $translationClass = $this->getTranslationClass($queryBuilder, $fieldPath);
        if ($translationClass === null) {
            return $pattern;
        }

        // unique in the whole query, and the same from a request to the next
        $t = 'crudit_tr_' . preg_replace('/\W/', '_', $this->id) . '_' . ltrim($aliasKey, '_');

        $subquery = 'SELECT ' . $t . '.id FROM ' . $translationClass . ' ' . $t
            . ' WHERE ' . $t . '.object = ' . $alias
            . ' AND ' . $t . ".field = '" . ltrim($col, '.') . "'"
            . ' AND ' . $this->getPattern($positiveOp, $col, $t, '.content', $paramname);

        return '(' . $pattern . $glue . '(' . $subquery . '))';
    }

    /**
     * Returns the Gedmo personal translation class of the entity holding the field (path like "relation:field"),
     * or null if the field is not translatable.
     */
    protected function getTranslationClass(QueryBuilder $queryBuilder, string $fieldPath): ?string
    {
        $em = $queryBuilder->getEntityManager();
        $listener = $this->getTranslatableListener($queryBuilder);
        if ($listener === null) {
            return null;
        }

        $metadata = $em->getClassMetadata($queryBuilder->getRootEntities()[0]);

        $associations = explode(':', $fieldPath);
        $field = array_pop($associations);
        foreach ($associations as $association) {
            $metadata = $em->getClassMetadata($metadata->getAssociationTargetClass($association));
        }

        $config = $listener->getConfiguration($em, $metadata->getName());
        if (!in_array($field, $config['fields'] ?? [], true) || !isset($config['translationClass'])) {
            return null;
        }

        // only personal translations (a translation table per entity, with an "object" relation)
        $translationClass = $config['translationClass'];

        return $em->getClassMetadata($translationClass)->hasAssociation('object') ? $translationClass : null;
    }

    /**
     * The listener is subscribed to postLoad: only the listeners of this event are asked (getAllListeners() would
     * create every lazy listener of the entity manager), once per event manager.
     */
    protected function getTranslatableListener(QueryBuilder $queryBuilder): ?TranslatableListener
    {
        $eventManager = $queryBuilder->getEntityManager()->getEventManager();
        self::$translatableListeners ??= new \WeakMap();

        if (!self::$translatableListeners->offsetExists($eventManager)) {
            $found = null;
            foreach ($eventManager->getListeners(Events::postLoad) as $listener) {
                if ($listener instanceof TranslatableListener) {
                    $found = $listener;
                    break;
                }
            }
            self::$translatableListeners[$eventManager] = $found;
        }

        return self::$translatableListeners[$eventManager];
    }
}
