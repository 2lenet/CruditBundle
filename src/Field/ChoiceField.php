<?php

namespace Lle\CruditBundle\Field;

use Symfony\Component\OptionsResolver\OptionsResolver;

class ChoiceField extends AbstractField
{
    public function support(string $type): bool
    {
        return (in_array($type, [self::class]));
    }

    protected function getDefaultTemplate(): ?string
    {
        return '@LleCrudit/field/choice.html.twig';
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        parent::configureOptions($optionsResolver);
        $optionsResolver->setDefaults([
            'choices' => null,
            'required' => false,
        ]);
    }
}
