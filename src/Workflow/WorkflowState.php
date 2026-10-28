<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Workflow;

/**
 * A place of a workflow and its tone (metadata `tone` of the place), for the Status component of CruditUiBundle. Its
 * label is the translation of `wf.state.<place>`.
 */
final class WorkflowState
{
    public function __construct(
        public readonly string $place,
        public readonly string $tone,
    ) {
    }
}
