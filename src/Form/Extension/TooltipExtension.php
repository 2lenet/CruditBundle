<?php

namespace Lle\CruditBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TooltipExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined(['tooltip', 'tooltip_html', 'tooltip_template', 'tooltip_position']);
        $resolver->setDefaults(
            ['tooltip' => null, 'tooltip_html' => null, 'tooltip_template' => null, 'tooltip_position' => null]
        );
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $view->vars['tooltip'] = $options['tooltip'];
        $view->vars['tooltip_html'] = $options['tooltip_html'];
        $view->vars['tooltip_template'] = $options['tooltip_template'];
        $view->vars['tooltip_position'] = $options['tooltip_position'];
    }
}
