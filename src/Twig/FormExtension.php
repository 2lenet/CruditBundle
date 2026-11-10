<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Twig;

use Symfony\Component\Form\FormView;
use Twig\Attribute\AsTwigFunction;

/**
 * Helpers of the form of a CRUD (brick/form/index.html.twig) and of the forms of the batch actions (lot 3.2.a).
 */
class FormExtension
{
    /** Compound fields shown as one input (their own widget), not laid out field by field */
    public const WIDGET_PREFIXES = ['choice', 'collection', 'date', 'time', 'datetime', 'dateinterval', 'birthday', 'week', 'repeated', 'vich_file', 'vich_image'];

    /**
     * A sub-form (a compound field of the form type, with fields) laid out field by field on the grid of the form.
     */
    #[AsTwigFunction('crudit_form_flattened')]
    public static function isFlattened(FormView $field): bool
    {
        if (!($field->vars['compound'] ?? false) || \count($field->children) === 0) {
            return false;
        }
        /** @var list<string> $prefixes */
        $prefixes = $field->vars['block_prefixes'] ?? [];
        foreach ($prefixes as $prefix) {
            if (\in_array($prefix, self::WIDGET_PREFIXES, true) || str_starts_with($prefix, 'crudit_')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether a field shown by the form is required, so that the legend of the required fields is shown: the fields
     * of a sub-form laid out field by field are looked at, not the sub-form (required by default even when all its
     * fields are optional). Sections, hidden fields and the CSRF token are not shown as fields.
     */
    #[AsTwigFunction('crudit_form_has_required')]
    public static function hasRequired(FormView $form): bool
    {
        return self::anyRequired($form, true);
    }

    /**
     * @param bool $flatten the fields of the form itself: a sub-form among them is laid out field by field, whereas
     *                      the fields of that sub-form are shown as they are (form_row)
     */
    private static function anyRequired(FormView $form, bool $flatten): bool
    {
        foreach ($form->children as $child) {
            if (($child->vars['isGroup'] ?? false) || ($child->vars['name'] ?? null) === '_token') {
                continue;
            }
            /** @var list<string> $prefixes */
            $prefixes = $child->vars['block_prefixes'] ?? [];
            if (\in_array('hidden', $prefixes, true)) {
                continue;
            }
            if ($flatten && self::isFlattened($child) ? self::anyRequired($child, false) : (bool) ($child->vars['required'] ?? false)) {
                return true;
            }
        }

        return false;
    }

    /**
     * A field still sized by a Bootstrap column class (col, col-6, col-auto, col-md, col-md-6…) in a form of Crudit is
     * told by a deprecation: its width is the option span. Only the forms shown by Crudit call it, a form of the
     * project with its own theme may use these classes.
     */
    #[AsTwigFunction('crudit_form_field_deprecations')]
    public static function fieldDeprecations(FormView $field): void
    {
        /** @var string $class */
        $class = $field->vars['attr']['class'] ?? '';
        if (preg_match('/(?:^|\s)col(?:-(?:sm|md|lg|xl|xxl))?(?:-\d+|-auto)?(?:\s|$)/', $class) === 1) {
            trigger_deprecation(
                '2lenet/crudit-bundle',
                '2.0',
                'The class "%s" of the field "%s" no longer sets its width in a Crudit form: use the option "span" (1, 2, 3 or "full").',
                $class,
                $field->vars['full_name'] ?? $field->vars['name'] ?? '',
            );
        }
    }
}
