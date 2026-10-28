<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Workflow;

/**
 * The status of an element in a workflow (WorkflowStates::view()): its current states (one for a state_machine, maybe
 * several for a workflow of type workflow) and the transitions the user may apply.
 */
final class WorkflowView
{
    /**
     * @param list<WorkflowState> $states
     * @param list<WorkflowTransition> $transitions
     * @param string                   $entityLabel the element, in a text (%entity%): ActionView::labelOf()
     */
    public function __construct(
        public readonly string $name,
        public readonly array $states,
        public readonly array $transitions,
        public readonly string $entityLabel = '',
    ) {
    }
}
