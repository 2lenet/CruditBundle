<?php

namespace Lle\CruditBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class HtmlType extends AbstractType
{
    private const ATTR_CLASS_NAME = "html-ckeditor";

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        //add attr class name if it's not already there
        $class = self::ATTR_CLASS_NAME;
        if (isset($view->vars["attr"]) && isset($view->vars["attr"]["class"])) {
            $class = $view->vars["attr"]["class"];
            if (!in_array(self::ATTR_CLASS_NAME, explode(' ', $class))) {
                $class = self::ATTR_CLASS_NAME . ' ' . $class;
            }
        }
        $view->vars["attr"]["class"] = trim($class);
    }

    public function getName(): string
    {
        return 'html_editor';
    }

    public function getBlockPrefix(): string
    {
        return 'html_editor';
    }

    public function getParent()
    {
        return TextType::class;
    }
}