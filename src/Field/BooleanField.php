<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Field;

use Lle\CruditBundle\Dto\Field\Field;
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

    public function configureOptions(Field $field): array
    {
        $optionResolver = new OptionsResolver();
        $optionResolver->setDefaults([
           'nullToFalse' => false
        ]);
        return $optionResolver->resolve($field->getOptions());
    }
}
