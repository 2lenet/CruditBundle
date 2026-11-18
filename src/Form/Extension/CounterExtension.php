<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Counter of the characters of a field with a maximum length (attr.maxlength, lot 3.2.b), from the theme of
 * CruditUiBundle: a long text has it, a text on one line has it with `counter: true` (Symfony puts the length of the
 * column of a string in maxlength, every text would have one); `counter: false` takes it away.
 */
class CounterExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [TextType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('counter', null);
        $resolver->setAllowedTypes('counter', ['null', 'bool']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if ($options['counter'] !== null) {
            $view->vars['counter'] = $options['counter'];
        }
    }
}
