<?php

namespace Lle\CruditBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Adds choice-tom-select class to ChoiceTypes, this way TomSelect will be applied.
 * Use option disable_tom_select to disable it.
 */
class TomSelectExtension extends AbstractTypeExtension
{
    public const TOMSELECT_CLASSNAME = 'choice-tom-select';

    public static function getExtendedTypes(): iterable
    {
        return [ChoiceType::class];
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $tomSelectDisabled = $options['disable_tom_select'] ?? false;
        $expanded = $options['expanded'] ?? null;

        if (!$tomSelectDisabled && $expanded === false) {
            $attr = $options['attr'] ?? [];

            if (array_key_exists('class', $attr)) {
                $attr['class'] = $attr['class'] . ' ' . self::TOMSELECT_CLASSNAME;
            } else {
                $attr['class'] = self::TOMSELECT_CLASSNAME;
            }

            $view->vars['attr'] = $attr;
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('disable_tom_select', false);
    }
}
