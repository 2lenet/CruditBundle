<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Field;

class ColorField extends AbstractField
{
    public function support(string $type): bool
    {
        return (in_array($type, ['string', 'text', self::class]));
    }

    public function getDefaultTemplate(): ?string
    {
        return '@LleCrudit/field/color.html.twig';
    }
}
