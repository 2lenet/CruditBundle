<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Field;

use Symfony\Component\OptionsResolver\OptionsResolver;

class BooleanField extends AbstractField
{
    public function support(string $type): bool
    {
        return (in_array($type, ['boolean', 'bool', self::class]));
    }

    public function getDefaultTemplate(): ?string
    {
        return '@LleCrudit/field/bool.html.twig';
    }

    public function configureOptions(OptionsResolver $optionResolver): void
    {
        parent::configureOptions($optionResolver);
        $optionResolver->setDefaults([
            'nullToFalse' => false,
            'edit_route' => null,
            'reverseColors' => false,
        ]);
    }
}
