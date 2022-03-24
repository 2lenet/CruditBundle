<?php

namespace Lle\CruditBundle\Field;

class MarkdownField extends AbstractField
{
    public function support(string $type): bool
    {
        return (in_array($type, ["text", "longtext", self::class]));
    }

    public function getDefaultTemplate(): ?string
    {
        return '@LleCrudit/field/markdown.html.twig';
    }
}
