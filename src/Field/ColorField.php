<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Field;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Environment;

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
