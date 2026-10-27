<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Field;

use Lle\CruditBundle\Dto\FieldView;

class JsonField extends AbstractField
{
    /**
     * A JSON decoded as objects (stdClass) is rendered as arrays: the template walks lists and pairs.
     *
     * @param mixed $value
     */
    public function render(FieldView $fieldView, $value, array $options = []): string
    {
        if (\is_object($value) && !$value instanceof \Traversable) {
            $value = json_decode((string) json_encode($value), true);
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
