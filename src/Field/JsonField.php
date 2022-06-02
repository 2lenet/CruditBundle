<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Field;

class JsonField extends AbstractField
{
    public function support(string $type): bool
    {
        return (in_array($type, ['json', 'array', self::class]));
    }

    public function getDefaultTemplate(): ?string
    {
        return '@LleCrudit/field/json.html.twig';
    }
}
