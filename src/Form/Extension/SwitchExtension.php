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
        if ($options['switch'] && !\in_array('radio', $view->vars['block_prefixes'] ?? [], true)) {
            /** @var array<string, mixed> $attr */
            $attr = $view->vars['attr'] ?? [];
            $view->vars['attr'] = ['role' => 'switch'] + $attr;
        }
    }
}
