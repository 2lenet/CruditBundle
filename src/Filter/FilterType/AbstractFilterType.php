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
    public function __construct($fieldname)
    {
        $this->columnName = $fieldname;
        $this->id = $fieldname;
        $this->label = "field." . strtolower(str_replace(".", "_", $fieldname));
        $this->alias = "root.";
    }

    protected string $columnName;
    protected bool $hidden = false;
    protected array $additionnalKeys = [];
    protected string $alias = 'root.';
    protected string $id;
    protected string $label = "";
    protected ?array $data = null;
    protected array $default = [];
    protected ?string $role = null;

    public function getOperators(): array
    {
        return [
        ];
    }

    /**
     * @return array
     */
    public function getAdditionnalKeys(): array
    {
        return $this->additionnalKeys;
    }

    /**
     * @param array $additionnalKeys
     * @return AbstractFilterType
     */
    public function setAdditionnalKeys(array $additionnalKeys): AbstractFilterType
    {
        $this->additionnalKeys = $additionnalKeys;

        return $this;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function setDefault($defaultData): self
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
     *
     * @return string
     */
    protected function getAlias()
    {
        return $this->alias;
    }

    public function isHidden()
    {
        return $this->hidden;
    }

    public function setHidden($hidden)
    {
        $this->hidden = $hidden;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    /**
     * Get the value of default
     */
    public function getDefault()
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

    /**
     * @param QueryBuilder $qb
     * @return array
     */
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

    /**
     * @return string|null
     */
    public function getRole(): ?string
    {
        return $this->role;
    }

    /**
     * @param string|null $role
     * @return self
     */
    public function setRole(?string $role): self
    {
        $this->role = $role;

        return $this;
    }
}
