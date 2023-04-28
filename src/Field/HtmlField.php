<?php

namespace Lle\CruditBundle\Field;

class HtmlField extends AbstractField
{
    public function support(string $type): bool
    {
        return (in_array($type, ["string", "text", "longtext", self::class]));
    }

    public function getDefaultTemplate(): ?string
    {
        return '@LleCrudit/field/html.html.twig';
    }
}