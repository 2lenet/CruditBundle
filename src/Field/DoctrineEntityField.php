<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Field;

class DoctrineEntityField extends AbstractField
{

    public function support(string $type): bool
    {
        return (in_array($type, [self::class]));
    }

    public function getDefaultTemplate(): ?string
    {
        return '@LleCrudit/field/doctrine_entity.html.twig';
    }
}
