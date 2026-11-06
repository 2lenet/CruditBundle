<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Width of a field in the grid of a Crudit form (lot 3.2.a): `span` 1 (default), 2 or 3 columns, or `full` (the whole
 * line). A field wider than its section (3 in a section of 2 columns) takes the whole line; so does a field of 3 columns
 * on a narrow screen, and every field on a phone. The class of the field (`attr.class`) no longer sets its width: a
 * Bootstrap column class (`col-md-6`) is told by a deprecation.
 */
class SpanExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('span', null);
        $resolver->setAllowedValues('span', [null, 1, 2, 3, 'full']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if (in_array($options['span'], [2, 3, 'full'], true)) {
            /** @var array<string, string> $rowAttr */
            $rowAttr = $view->vars['row_attr'] ?? [];
            $rowAttr['class'] = trim(($rowAttr['class'] ?? '') . ' crudit-form-field--span-' . $options['span']);
            $view->vars['row_attr'] = $rowAttr;
        }

        /** @var string $class */
        $class = $view->vars['attr']['class'] ?? '';
        if (preg_match('/(?:^|\s)col(?:-(?:sm|md|lg|xl|xxl))?(?:-\d+|-auto)?(?:\s|$)/', $class) === 1) {
            trigger_deprecation(
                '2lenet/crudit-bundle',
                '2.0',
                'The class "%s" of the field "%s" no longer sets its width in a Crudit form: use the option "span" (1, 2, 3 or "full").',
                $class,
                $view->vars['full_name'] ?? $form->getName(),
            );
        }
    }
}
