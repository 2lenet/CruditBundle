<?php

namespace Lle\CruditBundle\Field;

class CKEditorField extends AbstractField
{
    public function support(string $type): bool
    {
        return in_array($type, ['string', 'text', 'longtext', self::class]);
    }

    public function isWide(): bool
    {
        return true;
    }

    protected function getDefaultTemplate(): ?string
    {
        return '@LleCrudit/field/ckeditor.html.twig';
    }
}
