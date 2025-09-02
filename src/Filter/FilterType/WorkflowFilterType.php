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
        if (!isset($this->data['op'])) {
            return;
        }

        $op = $this->data['op'];

        [$column, $alias, $paramname] = $this->getQueryParams($queryBuilder);

        $query = $this->getPattern($op, $column, $alias, $column, $paramname);
        $this->applyAdditionnalFields($queryBuilder, $query, $op, $paramname);

        if (in_array($op, [FilterTypeInterface::OPERATOR_IS_NULL, FilterTypeInterface::OPERATOR_IS_NOT_NULL])) {
            $queryBuilder->andWhere($query);
        } elseif (isset($this->data['value']) && $this->data['value']) {
            $value = explode(',', $this->data['value']);

            $queryBuilder->setParameter($paramname, $value);
            $queryBuilder->andWhere($query);
        }

        $this->applyAdditionnalConditions($queryBuilder);
    }

    public function getOperators(): array
    {
        return [
            FilterTypeInterface::OPERATOR_IN => ['icon' => 'fas fa-equals'],
            FilterTypeInterface::OPERATOR_NOT_IN => ['icon' => 'fas fa-not-equal'],
            FilterTypeInterface::OPERATOR_IS_NULL => ['icon' => 'far fa-square'],
            FilterTypeInterface::OPERATOR_IS_NOT_NULL => ['icon' => 'fas fa-square'],
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
        if (is_array($data['value'])) {
            return in_array($value, $data['value']);
        } else {
            return ($data['value'] === $value);
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

        $this->data['items'] = json_encode($items);

        return $this;
    }
}
