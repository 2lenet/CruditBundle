<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Field;

use Symfony\Component\OptionsResolver\OptionsResolver;

class DoctrineEntityField extends AbstractField
{
    public function support(string $type): bool
    {
        // this field is set by the FieldResolver for Doctrine relations
        return (in_array($type, [self::class]));
    }

    public function getDefaultTemplate(): ?string
    {
        return '@LleCrudit/field/doctrine_entity.html.twig';
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        parent::configureOptions($optionsResolver);

        $optionsResolver->setDefaults([
            'link' => true,
            'route' => null,
        ]);
    }
}
