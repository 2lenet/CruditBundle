<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Workflow;

use Lle\CruditBundle\Action\ActionView;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * States and transitions of an element, for WorkflowField and the counters.
 *
 * - The colour of a state is the metadata `tone` of its place: a tone of CruditUiBundle (neutral, info, primary,
 *   success, warning, danger), a hue of its extended palette (blue, teal…) or a colour of the project; neutral by
 *   default.
 * - A transition is offered if the workflow allows it (its guards) and if the user has the role
 *   ROLE_<WORKFLOW>_WF_<TRANSITION> (upper case, "-" turned into "_"), as in 1.x, asked with the element as subject:
 *   a voter of the project may decide element by element (its perimeter: company, own elements…).
 * - The marking is read from the marking store, never by Workflow::getMarking(): on an element without state, that one
 *   puts it in the initial place and dispatches the events "entered" (while a page is displayed). An element without
 *   state has no state and no transition (the field shows "Not set").
 */
class WorkflowStates
{
    public const DEFAULT_TONE = 'neutral';

    public function __construct(
        private Registry $registry,
        private AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    /**
     * The workflow of the element: the one named, else the first one it supports (as 1.x).
     */
    public function workflow(object $subject, ?string $name = null): WorkflowInterface
    {
        if ($name !== null && $name !== '') {
            return $this->registry->get($subject, $name);
        }

        $workflows = $this->registry->all($subject);
        if ($workflows === []) {
            throw new \InvalidArgumentException(sprintf('No workflow supports the class "%s".', $subject::class));
        }

        return $workflows[0];
    }

    /**
     * @param bool $withTransitions false: the states only (a field displayed outside a CRUD, which cannot apply them)
     */
    public function view(object $subject, ?string $name = null, bool $withTransitions = true): WorkflowView
    {
        $workflow = $this->workflow($subject, $name);

        $states = [];
        foreach ($this->places($workflow, $subject) as $place) {
            $states[] = $this->state($workflow, $place);
        }

        $transitions = [];
        // getEnabledTransitions() would mark an element without state (Workflow::getMarking())
        $enabled = $withTransitions && $states !== [] ? $workflow->getEnabledTransitions($subject) : [];
        foreach ($enabled as $transition) {
            $transitionName = $transition->getName();
            // The same name from several places (a state machine): offered once.
            if (
                isset($transitions[$transitionName])
                || !$this->isGranted($workflow->getName(), $transitionName, $subject)
            ) {
                continue;
            }

            $tos = $transition->getTos();
            $metadata = $workflow->getMetadataStore()->getTransitionMetadata($transition);
            $transitions[$transitionName] = new WorkflowTransition(
                $transitionName,
                $this->state($workflow, (string) reset($tos)),
                ($metadata['danger'] ?? false) === true,
            );
        }

        $id = method_exists($subject, 'getId') ? $subject->getId() : null;

        return new WorkflowView($workflow->getName(), $states, array_values($transitions), ActionView::labelOf($subject, $id));
    }

    public function state(WorkflowInterface $workflow, string $place): WorkflowState
    {
        $tone = $workflow->getMetadataStore()->getPlaceMetadata($place)['tone'] ?? null;

        return new WorkflowState($place, is_string($tone) && $tone !== '' ? $tone : self::DEFAULT_TONE);
    }

    /**
     * The places of the element, read from the marking store (without side effect); empty if it has no state.
     *
     * @return list<string>
     */
    public function places(WorkflowInterface $workflow, object $subject): array
    {
        return array_map('strval', array_keys($workflow->getMarkingStore()->getMarking($subject)->getPlaces()));
    }

    public function isGranted(string $workflowName, string $transition, ?object $subject = null): bool
    {
        return $this->authorizationChecker->isGranted(self::role($workflowName, $transition), $subject);
    }

    public static function role(string $workflowName, string $transition): string
    {
        return 'ROLE_' . strtoupper($workflowName) . '_WF_' . str_replace('-', '_', strtoupper($transition));
    }
}
