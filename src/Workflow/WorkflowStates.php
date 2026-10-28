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
 *   ROLE_<WORKFLOW>_WF_<TRANSITION> (upper case, "-" turned into "_"), as in 1.x.
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

    public function view(object $subject, ?string $name = null): WorkflowView
    {
        $workflow = $this->workflow($subject, $name);

        $states = [];
        foreach (array_keys($workflow->getMarking($subject)->getPlaces()) as $place) {
            $states[] = $this->state($workflow, (string) $place);
        }

        $transitions = [];
        foreach ($workflow->getEnabledTransitions($subject) as $transition) {
            $transitionName = $transition->getName();
            // The same name from several places (a state machine): offered once.
            if (isset($transitions[$transitionName]) || !$this->isGranted($workflow->getName(), $transitionName)) {
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

    public function isGranted(string $workflowName, string $transition): bool
    {
        return $this->authorizationChecker->isGranted(self::role($workflowName, $transition));
    }

    public static function role(string $workflowName, string $transition): string
    {
        return 'ROLE_' . strtoupper($workflowName) . '_WF_' . str_replace('-', '_', strtoupper($transition));
    }
}
