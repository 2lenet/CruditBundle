<?php

namespace Lle\CruditBundle\Form\Type;

use Lle\CruditBundle\Contracts\SanitizerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class GroupType extends AbstractType
{
    protected SanitizerInterface $sanitizer;

    public function __construct(SanitizerInterface $sanitizer)
    {
        $this->sanitizer = $sanitizer;
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);
        $view->vars['isGroup'] = true;
        $view->vars['fieldset_class'] = "col-12";
        if (isset($view->vars["attr"]) && isset($view->vars["attr"]["class"])) {
            $class = $view->vars["attr"]["class"];
            $view->vars['fieldset_class'] = $class;
        }
    }

    public function getName(): string
    {
        return 'crudit_group';
    }
}
