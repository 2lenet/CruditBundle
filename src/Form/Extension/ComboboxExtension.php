<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A choice in a list with search (lot 3.2.c): the list of CruditUiBundle (variable combobox of its form theme) in place
 * of Tom Select. By default (null) a list with several answers (`multiple`, not `expanded`); a list with one answer
 * stays native. `combobox: true` asks it for a list with one answer (a long one), `false` keeps the native list.
 */
class ComboboxExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [ChoiceType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('combobox', null);
        $resolver->setAllowedTypes('combobox', ['bool', 'null']);
        $resolver->setNormalizer('combobox', static function (Options $options, ?bool $combobox): bool {
            if ($combobox && $options['expanded']) {
                throw new InvalidOptionsException('The option "combobox" needs a choice without "expanded": a list, not boxes.');
            }

            return $combobox ?? ($options['multiple'] && !$options['expanded']);
        });
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['combobox'] = $options['combobox'];
    }
}
