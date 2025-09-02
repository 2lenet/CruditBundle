<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Doctrine\ORM\QueryBuilder;
use Lle\CruditBundle\Contracts\FilterTypeInterface;

/**
 * Abstract base class for all admin list filters
 */
abstract class AbstractFilterType implements FilterTypeInterface
{
    protected string $columnName;
    protected bool $hidden = false;
    protected array $additionnalKeys = [];
    protected string $alias = 'root.';
    protected string $id;
    protected string $label = '';
    protected ?array $data = null;
    protected array $default = [];
    protected ?string $role = null;
    protected array $additionnalFields = [];

    public function __construct(string $fieldname)
    {
        $this->columnName = $fieldname;
        $this->id = $fieldname;
        $this->label = 'field.' . strtolower(str_replace('.', '_', $fieldname));
        $this->alias = 'root.';
    }

    public function getOperators(): array
    {
        return [];
    }

    public function getAdditionnalKeys(): array
    {
        return $this->additionnalKeys;
    }

    public function setAdditionnalKeys(array $additionnalKeys): static
    {
        $this->additionnalKeys = $additionnalKeys;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function setDefault(array $defaultData): static
    {
        $this->default = $defaultData;

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Returns empty string if no alias, otherwise make sure the alias has just one '.' after it.
     */
    protected function getAlias(): string
    {
        return $this->alias;
    }

    public function isHidden(): bool
    {
        return $this->hidden;
    }

    public function setHidden(bool $hidden): static
    {
        $this->hidden = $hidden;

        return $this;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(?array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get the value of default
     */
    public function getDefault(): array
    {
        return $this->default;
    }

    public function getStateTemplate(): string
    {
        $fqcn = get_class($this);
        /** @var string $filterType */
        $filterType = preg_replace(
            '/Lle\\\\CruditBundle\\\\Filter\\\\FilterType\\\\(\w+)FilterType/',
            '${1}',
            $fqcn
        );
        $name = strtolower($filterType);

        return "@LleCrudit/filter/state/{$name}_filter.html.twig";
    }

    public function getTemplate(): string
    {
        $fqcn = get_class($this);
        /** @var string $filterType */
        $filterType = preg_replace(
            '/Lle\\\\CruditBundle\\\\Filter\\\\FilterType\\\\(\w+)FilterType/',
            '${1}',
            $fqcn
        );
        $name = strtolower($filterType);

        return "@LleCrudit/filter/type/{$name}_filter.html.twig";
    }

    protected function getQueryParams(QueryBuilder $qb, ?string $additionnalField = null): array
    {
        // parts (e.g. : user:post:title => [user, post, title]
        $fields = explode(':', $additionnalField ?? $this->id);

        // join alias
        $alias = null;

        // column to join (i.e. root.user, user.post, etc.)
        $join = $qb->getRootAliases()[0];

        $field = array_shift($fields);

        // while we aren't at the last part
        while (!empty($fields)) {
            $alias = $alias ? $alias . '_' . $field : $field;

            if (!in_array($alias, $qb->getAllAliases())) {
                $qb->leftJoin($join . '.' . $field, $alias);
            }

            $join = $alias;
            $field = array_shift($fields);
        }

        $paramname = str_replace('.', '_', $alias . '_' . $field);

        return [
            '.' . $field,
            $alias ?? $join,
            $paramname,
        ];
    }

    public function getPattern(string $op, string $id, string $alias, string $col, string $paramname): ?string
    {
        $pattern = null;
        switch ($op) {
            case self::OPERATOR_EQUAL:
                $pattern = $alias . $col . ' = :' . $paramname;
                break;
            case self::OPERATOR_NOT_EQUAL:
                $pattern = $alias . $col . ' <> :' . $paramname;
                break;
            case self::OPERATOR_IN:
                $pattern = $alias . $col . ' IN (:' . $paramname . ')';
                break;
            case self::OPERATOR_NOT_IN:
                $pattern = $alias . $col . ' NOT IN (:' . $paramname . ')';
                break;
            case self::OPERATOR_BEFORE:
            case self::OPERATOR_LESS_THAN:
                $pattern = $alias . $col . ' < :' . $paramname;
                break;
            case self::OPERATOR_AFTER:
            case self::OPERATOR_GREATER_THAN:
                $pattern = $alias . $col . ' > :' . $paramname;
                break;
            case self::OPERATOR_IS_NULL:
                $pattern = $alias . $col . ' IS NULL OR ' . $alias . $col . ' = "" ';
                break;
            case self::OPERATOR_IS_NOT_NULL:
                $pattern = $alias . $col . ' IS NOT NULL AND ' . $alias . $col . ' <> "" ';
                break;
            case self::OPERATOR_LESS_THAN_EQUAL:
                $pattern = $alias . $col . ' <= :' . $paramname;
                break;
            case self::OPERATOR_GREATER_THAN_EQUAL:
                $pattern = $alias . $col . ' >= :' . $paramname;
                break;
            case self::OPERATOR_CONTAINS:
            case self::OPERATOR_STARTS_WITH:
            case self::OPERATOR_ENDS_WITH:
                $pattern = $alias . $col . ' LIKE :' . $paramname;
                break;
            case self::OPERATOR_DOES_NOT_CONTAIN:
                $pattern = $alias . $col . ' NOT LIKE :' . $paramname;
                break;
        }

        return $pattern ? '(' . $pattern . ')' : null;
    }

    public function applyAdditionnalFields(
        QueryBuilder $queryBuilder,
        string &$query,
        string $op,
        string $paramname
    ): void {
        foreach ($this->additionnalFields as $additionnalField) {
            [$additionnalColumn, $additionnalAlias] = $this->getQueryParams($queryBuilder, $additionnalField);
            $query .= ' OR ' . $this->getPattern(
                $op,
                $additionnalColumn,
                $additionnalAlias,
                $additionnalColumn,
                $paramname
            );
        }
    }

    public function applyAdditionnalConditions(QueryBuilder $queryBuilder): void
    {
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getAdditionnalFields(): array
    {
        return $this->additionnalFields;
    }

    public function setAdditionnalFields(array $additionnalFields): static
    {
        $this->additionnalFields = $additionnalFields;

        return $this;
    }
}
