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
    /** Inputs where the browser could fill in the data of another person: the 1.x keeps its autofill away */
    public const TEXT_PREFIXES = ['text', 'number', 'integer', 'money', 'percent'];

    /** Children of TextType that are not typed in */
    public const NOT_TYPED_PREFIXES = ['color', 'range', 'hidden'];

    /** Format helpers of the 1.x, classes of a field (controller input-format) */
    public const FORMAT_CLASSES = ['crudit-upper-format', 'crudit-lower-format', 'crudit-email-format', 'crudit-ip-format'];

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

    /**
     * Attributes of an input with a format helper of the 1.x (a class of FORMAT_CLASSES, lot 3.2.b): the controller
     * input-format, no spell check on an e-mail or an IP address, the keyboard of an e-mail address (an input of type
     * text). An IP address keeps the keyboard of a text: the keyboard decimal of iOS has the comma of the language, not
     * the point. What the field gives itself (attr) is kept.
     *
     * @param array<string, mixed> $attr
     *
     * @return array<string, mixed>
     */
    #[AsTwigFunction('crudit_form_format_attr')]
    public static function formatAttr(array $attr): array
    {
        $class = $attr['class'] ?? '';
        $formats = array_intersect(preg_split('/\s+/', \is_string($class) ? $class : '', -1, \PREG_SPLIT_NO_EMPTY) ?: [], self::FORMAT_CLASSES);
        if ($formats === []) {
            return $attr;
        }

        $controller = $attr['data-controller'] ?? '';
        $attr['data-controller'] = trim((\is_string($controller) ? $controller : '') . ' 2lenet--crudit-bundle--input-format');
        $email = \in_array('crudit-email-format', $formats, true);
        $ip = \in_array('crudit-ip-format', $formats, true);
        if ($email || $ip) {
            $attr += ['spellcheck' => 'false'];
        }
        if ($email) {
            $attr += ['inputmode' => 'email'];
        }

        return $attr;
    }

    /**
     * Attributes of a field of a CRUD form (brick/form/index.html.twig): as in the 1.x, autocomplete="new-password" keeps
     * the autofill of the browser away (the data of another person), but only on an input that is typed in (text,
     * e-mail, phone, number, long text…), not on a checkbox, a list or a compound field (lot 3.2.b). What the field
     * gives itself (attr.autocomplete) is kept.
     *
     * @return array<string, mixed>
     */
    #[AsTwigFunction('crudit_form_field_attr')]
    public static function fieldAttr(FormView $field): array
    {
        /** @var array<string, mixed> $attr */
        $attr = $field->vars['attr'] ?? [];
        /** @var list<string> $prefixes */
        $prefixes = $field->vars['block_prefixes'] ?? [];
        $typed = !($field->vars['compound'] ?? false)
            && array_intersect($prefixes, self::TEXT_PREFIXES) !== []
            && array_intersect($prefixes, self::NOT_TYPED_PREFIXES) === [];

        return $typed ? $attr + ['autocomplete' => 'new-password'] : $attr;
    }
}
