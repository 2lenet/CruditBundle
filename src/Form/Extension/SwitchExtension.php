<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A checkbox shown as a switch (lot 3.2.b): option `switch`, the switch of CruditUiBundle (`role="switch"`, its state
 * written). The guesser of Crudit sets it on a boolean of an entity; `switch: false` keeps the checkbox. An option
 * rather than `attr.role`: the `attr` of the project replaces the one of the guesser.
 *
 * Only the variable `switch` of the view is set: the theme of Crudit makes it `role="switch"` when it draws the field.
 * A form of the project drawn with another theme (Bootstrap) keeps a checkbox, told as a checkbox; the guesser applies
 * to all the forms of the project.
 */
class SwitchExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [CheckboxType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('switch', false);
        $resolver->setAllowedTypes('switch', 'bool');
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['switch'] = $options['switch'] && !\in_array('radio', $view->vars['block_prefixes'] ?? [], true);
    }
}
