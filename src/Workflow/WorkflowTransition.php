<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Workflow;

/**
 * A transition that the user may apply: its name, the state it leads to (shown in the menu, not the name of the
 * transition) and whether it is destructive (metadata `danger: true`: the confirmation in danger).
 */
final class WorkflowTransition
{
    public function __construct(
        public readonly string $name,
        public readonly WorkflowState $to,
        public readonly bool $danger,
    ) {
    }
}
