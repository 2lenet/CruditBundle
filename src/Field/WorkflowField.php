<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Field;

class WorkflowField extends AbstractField
{
    public function support(string $type): bool
    {
        return (in_array($type, ["workflow", self::class]));
    }

    public function getDefaultTemplate(): ?string
    {
        return "@LleCrudit/field/workflow.html.twig";
    }
}
