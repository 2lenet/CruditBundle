<?php

namespace Lle\CruditBundle\Form\Type;

use Lle\CruditBundle\Contracts\SanitizerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class MarkdownType extends AbstractType
{
    private const ATTR_CLASS_NAME = "markdown-editor";
    protected SanitizerInterface $sanitizer;

    public function __construct(SanitizerInterface $sanitizer)
    {
        $this->sanitizer = $sanitizer;
    }

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

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $event->setData($this->sanitizer->sanitize($event->getData()));
        });
    }

    public function getParent()
    {
        return TextareaType::class;
    }

    public function getName()
    {
        return 'crudit_markdown';
    }
}
