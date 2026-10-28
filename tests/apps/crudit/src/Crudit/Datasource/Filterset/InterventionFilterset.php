<?php

declare(strict_types=1);

namespace App\Crudit\Datasource\Filterset;

use Lle\CruditBundle\Datasource\AbstractFilterSet;
use Lle\CruditBundle\Filter\FilterType\WorkflowFilterType;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * The filter on the state of the interventions, which the counters fill (lot 3.4.c).
 */
class InterventionFilterset extends AbstractFilterSet
{
    public function __construct(private readonly WorkflowInterface $interventionStateMachine)
    {
    }

    public function getFilters(): array
    {
        return [
            WorkflowFilterType::new('status', $this->interventionStateMachine),
        ];
    }
}
