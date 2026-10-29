<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Field;

use Lle\CruditBundle\Dto\FieldView;

class JsonField extends AbstractField
{
    /**
     * A JSON decoded as objects (stdClass), at any depth, is rendered as arrays: the template walks lists and pairs.
     * A value that cannot be encoded (invalid UTF-8) is kept as it is, not shown as empty.
     *
     * @param mixed $value
     */
    public function render(FieldView $fieldView, $value, array $options = []): string
    {
        if ($value !== null && !\is_scalar($value) && !$value instanceof \Traversable) {
            try {
                $value = json_decode(json_encode($value, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                // kept as it is
            }
        }

        return parent::render($fieldView, $value, $options);
    }

    public function support(string $type): bool
    {
        return (in_array($type, ['json', 'array', self::class]));
    }

    public function getDefaultTemplate(): ?string
    {
        return '@LleCrudit/field/json.html.twig';
    }
}
