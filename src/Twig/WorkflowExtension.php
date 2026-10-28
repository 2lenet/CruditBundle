<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Twig;

use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Workflow\WorkflowCount;
use Lle\CruditBundle\Workflow\WorkflowCounter;
use Lle\CruditBundle\Workflow\WorkflowStates;
use Lle\CruditBundle\Workflow\WorkflowView;
use Twig\Attribute\AsTwigFunction;

/**
 * - `crudit_workflow(resource, name)`: the states of an element and the transitions the user may
 *   apply (WorkflowStates), for field/workflow.html.twig;
 * - `crudit_workflow_counters(class, name, crud, filter)`: the number of elements in each state (WorkflowCounter),
 *   for the Counters of CruditUiBundle; with a CrudConfig and the id of its WorkflowFilterType, each one links to the
 *   list filtered on its state.
 */
class WorkflowExtension
{
    public function __construct(private WorkflowStates $states, private WorkflowCounter $counter)
    {
    }

    #[AsTwigFunction('crudit_workflow')]
    public function workflow(object $resource, ?string $name = null): WorkflowView
    {
        return $this->states->view($resource, $name);
    }

    /**
     * @param class-string $class
     *
     * @return list<WorkflowCount>
     */
    #[AsTwigFunction('crudit_workflow_counters')]
    public function counters(
        string $class,
        ?string $name = null,
        ?CrudConfigInterface $crud = null,
        ?string $filter = null,
    ): array
    {
        return $this->counter->count($class, $name, null, $crud, $filter);
    }
}
