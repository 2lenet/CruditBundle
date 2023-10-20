<?php

namespace Lle\CruditBundle\Form\Type;

use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;

class HtmlType extends AbstractType
{
    public function getParent(): string
    {
        return CKEditorType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'crudit_html';
    }
}