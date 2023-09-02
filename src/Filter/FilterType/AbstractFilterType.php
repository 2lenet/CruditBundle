<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Doctrine\ORM\QueryBuilder;
use Lle\CruditBundle\Contracts\FilterTypeInterface;

/**
 * AbstractFilterType
 *
 * Abstract base class for all admin list filters
 */
abstract class AbstractFilterType implements FilterTypeInterface
{
    protected string $columnName;
    protected bool $hidden = false;
    protected array $additionnalKeys = [];
    protected string $alias = 'root.';
    protected string $id;
    protected string $label = "";
    protected ?array $data = null;
    protected array $default = [];
    protected ?string $role = null;

    public function __construct(string $fieldname)
    {
        $this->columnName = $fieldname;
        $this->id = $fieldname;
        $this->label = "field." . strtolower(str_replace(".", "_", $fieldname));
        $this->alias = "root.";
    }

    public function getOperators(): array
    {
        return [
        ];
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

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function setDefault(array $defaultData): self
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

    public function setHidden(bool $hidden): self
    {
        $this->hidden = $hidden;

        return $this;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(?array $data): self
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
        $name = strtolower(
            preg_replace(
                "/Lle\\\\CruditBundle\\\\Filter\\\\FilterType\\\\(\w+)FilterType/",
                '${1}',
                $fqcn
            )
        );

        return "@LleCrudit/filter/state/{$name}_filter.html.twig";
    }

    public function getTemplate(): string
    {
        $fqcn = get_class($this);
        $name = strtolower(
            preg_replace(
                "/Lle\\\\CruditBundle\\\\Filter\\\\FilterType\\\\(\w+)FilterType/",
                '${1}',
                $fqcn
            )
        );

        return "@LleCrudit/filter/type/{$name}_filter.html.twig";
    }

    protected function getQueryParams(QueryBuilder $qb): array
    {
        // parts (e.g. : user:post:title => [user, post, title]
        $fields = explode(':', $this->id);

        // join alias
        $alias = null;

        // column to join (i.e. root.user, user.post, etc.)
        $join = $qb->getRootAliases()[0];

        $field = array_shift($fields);

        // while we aren't at the last part
        while (!empty($fields)) {
            $alias = $alias ? $alias . "_" . $field : $field;

            if (!in_array($alias, $qb->getAllAliases())) {
                $qb->leftJoin($join . "." . $field, $alias);
            }

            $join = $alias;
            $field = array_shift($fields);
        }

        $paramname = str_replace('.', '_', $alias . "_" . $field);

        return [
            "." . $field,
            $alias ?? $join,
            $paramname,
        ];
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): self
    {
        $this->role = $role;

        return $this;
    }
}
