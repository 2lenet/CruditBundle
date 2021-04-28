<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Field;

class TextField extends AbstractField
{
    public function support(string $type): bool
    {
        return (in_array($type, ['integer', 'float', 'string', 'decimal', 'text', self::class]));
    }

    public function getDefaultTemplate(): ?string
    {
        return '@LleCrudit/field/text.html.twig';
    }
}
