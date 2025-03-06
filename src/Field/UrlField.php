<?php

namespace Lle\CruditBundle\Field;

class UrlField extends AbstractField
{
    public function support(string $type): bool
    {
        return (in_array($type, ['url', self::class]));
    }

    protected function getDefaultTemplate(): ?string
    {
        return '@LleCrudit/field/url.html.twig';
    }
}
