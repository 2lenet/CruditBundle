<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Doctrine\ORM\QueryBuilder;
use Lle\CruditBundle\Contracts\FilterTypeInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class WorkflowFilterType extends AbstractFilterType
{
    protected WorkflowInterface $workflow;

    public static function new(string $fieldname, WorkflowInterface $workflow): static
    {
        return (new static($fieldname))
            ->setWorkflow($workflow)
            ->setAdditionnalKeys(['items']);
    }

    public function apply(QueryBuilder $queryBuilder): void
    {
        if (!isset($this->data["op"])) {
            return;
        }

        $op = $this->data["op"];

        [$column, $alias, $paramname] = $this->getQueryParams($queryBuilder);

        $query = $this->getPattern($op, $column, $alias, $column, $paramname);

        if (in_array($op, [FilterTypeInterface::OPERATOR_IS_NULL, FilterTypeInterface::OPERATOR_IS_NOT_NULL])) {
            $queryBuilder->andWhere($query);
        } elseif (
            isset($this->data["value"])
            && $this->data["value"]
        ) {
            $ids = explode(",", $this->data["value"]);

            $queryBuilder->setParameter($paramname, $ids);
            $queryBuilder->andWhere($query);
        }
    }

    private function getPattern(string $op, string $id, string $alias, string $col, string $paramname): ?string
    {
        $pattern = null;
        switch ($op) {
            case FilterTypeInterface::OPERATOR_IS_NULL:
                $pattern = $alias . $col . " IS NULL OR " . $alias . $col . " = '' ";
                break;
            case FilterTypeInterface::OPERATOR_IS_NOT_NULL:
                $pattern = $alias . $col . " IS NOT NULL AND " . $alias . $col . " <> '' ";
                break;
            case FilterTypeInterface::OPERATOR_EQUAL:
                $pattern = $alias . $col . " IN (:" . $paramname . ")";
                break;
            case FilterTypeInterface::OPERATOR_NOT_EQUAL:
                $pattern = $alias . $col . " NOT IN (:" . $paramname . ")";
                break;
        }

        return $pattern ? "(" . $pattern . ")" : null;
    }

    public function getOperators(): array
    {
        return [
            FilterTypeInterface::OPERATOR_EQUAL => ["icon" => "fas fa-equals"],
            FilterTypeInterface::OPERATOR_NOT_EQUAL => ["icon" => "fas fa-not-equal"],
            FilterTypeInterface::OPERATOR_IS_NULL => ["icon" => "far fa-square"],
            FilterTypeInterface::OPERATOR_IS_NOT_NULL => ["icon" => "fas fa-square"],
        ];
    }

    public function getChoices(): array
    {
        $choices = [];

        foreach ($this->workflow->getDefinition()->getPlaces() as $place) {
            $choices[] = $place;
        }

        return $choices;
    }

    public function isSelected(array $data, string $value): bool
    {
        if (is_array($data["value"])) {
            return in_array($value, $data["value"]);
        } else {
            return ($data["value"] === $value);
        }
    }

    public function getWorkflow(): WorkflowInterface
    {
        return $this->workflow;
    }

    public function setWorkflow(WorkflowInterface $workflow): static
    {
        $this->workflow = $workflow;

        return $this;
    }

    public function setData(?array $data): static
    {
        parent::setData($data);

        $items = [];
        if (!isset($data['items']) && isset($data['value']) && $data['value'] !== '') {
            foreach (explode(',', $data['value']) as $value) {
                $items[] = ['id' => $value];
            }
        }

        $this->data["items"] = json_encode($items);

        return $this;
    }
}
