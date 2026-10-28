<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Workflow;

/**
 * The number of elements in a state (WorkflowCounter), for a Counter of CruditUiBundle: its label is the translation
 * of `wf.state.<place>`, its link the list filtered on the state (null without a filter to open).
 */
final class WorkflowCount
{
    public function __construct(
        public readonly WorkflowState $state,
        public readonly int $count,
        public readonly ?string $url = null,
    ) {
    }
}
