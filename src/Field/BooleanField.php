<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Field;

use Lle\CruditBundle\Contracts\FieldInterface;
use Lle\CruditBundle\Dto\FieldView;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;

class BooleanField implements FieldInterface
{
    /** @var Translator  */
    private $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }
    
    public function support(string $type): bool
    {
        return (in_array($type, ['boolean', 'bool', self::class]));
    }

    /** @param mixed $value */
    public function buildView(FieldView $fieldView, $value): FieldView
    {
        return $fieldView->setStringValue(($value) ? $this->trans('crudit.yes') : $this->trans('crudit.no'));
    }


    protected function trans($asset)
    {
        return $this->translator->trans($asset);
    }
}
