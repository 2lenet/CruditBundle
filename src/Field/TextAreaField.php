<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Field;

class TextAreaField extends AbstractField
{
    public function support(string $type): bool
    {
        return (in_array($type, ["text", "longtext", self::class]));
    }

    public function getDefaultTemplate(): ?string
    {
        return "@LleCrudit/field/textarea.html.twig";
    }
}
