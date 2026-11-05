<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Builder;

use Doctrine\ORM\EntityManagerInterface;
use Lle\CruditBundle\Contracts\FilterSetInterface;
use Lle\CruditBundle\Contracts\FilterTypeInterface;
use Lle\CruditBundle\Dto\BrickView;
use Lle\CruditBundle\Filter\FilterType\AbstractFilterType;
use Lle\CruditBundle\Filter\FilterType\BooleanFilterType;
use Lle\CruditBundle\Filter\FilterType\ChoiceFilterType;
use Lle\CruditBundle\Filter\FilterType\DateFilterType;
use Lle\CruditBundle\Filter\FilterType\DateTimeFilterType;
use Lle\CruditBundle\Filter\FilterType\EntityFilterType;
use Lle\CruditBundle\Filter\FilterType\NumberFilterType;
use Lle\CruditBundle\Filter\FilterType\NumberRangeFilterType;
use Lle\CruditBundle\Filter\FilterType\PeriodeFilterType;
use Lle\CruditBundle\Filter\FilterType\StringFilterType;
use Lle\CruditBundle\Filter\FilterType\TreeFilterType;
use Lle\CruditBundle\Filter\FilterType\WorkflowFilterType;
use Lle\CruditUiBundle\Format\Formatter;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Error\LoaderError;

/**
 * The data of the Filter:Bar of CruditUiBundle (`LleCruditUi:Filter:Bar`) for a list (FilterBrick): the filters of the
 * set, as data. The component knows nothing of Crudit; the rights, the translation of the labels, the choices and the
 * value in words (the chips, the buttons) are done here.
 *
 * Each filter of `filters` has the keys of `Filter:Field` (`name`, `label`, `type`, `operators`, `operator`, `value`,
 * `to`, `choices`) and `id`, `primary`, `hidden`, `active`, `summary`.
 *
 * **A filter of a type that keeps the default template is a `Filter:Field`.** One of a project that sets its own
 * template (`setTemplate()`), or a type of the project, keeps it: the filter then has `template` and `context` and the bar
 * includes it, with the variables of the 1.x (`id`, `data`, `filter`, `filtertype`, `view`).
 */
class FilterViewBuilder
{
    /** The most choices of an entity filter: the list is native until the autocomplete of the form (lot 3.2.c). */
    public const MAX_CHOICES = 200;

    /** The types of CruditBundle, the most specific first: the type of the field, the family of the default template. */
    private const TYPES = [
        [WorkflowFilterType::class, 'choice', 'workflow'],
        [TreeFilterType::class, 'choice', 'tree'],
        [EntityFilterType::class, 'choice', 'entity'],
        [ChoiceFilterType::class, 'choice', 'choice'],
        [BooleanFilterType::class, 'boolean', 'boolean'],
        [NumberRangeFilterType::class, 'range', 'numberrange'],
        [NumberFilterType::class, 'number', 'number'],
        [DateTimeFilterType::class, 'datetime', 'datetime'],
        [DateFilterType::class, 'date', 'date'],
        [PeriodeFilterType::class, 'period', 'periode'],
        [StringFilterType::class, 'text', 'string'],
    ];

    /** The pictogram of the catalogue of CruditUiBundle of each operator. */
    private const ICONS = [
        FilterTypeInterface::OPERATOR_EQUAL => 'operator-equals',
        FilterTypeInterface::OPERATOR_NOT_EQUAL => 'operator-not-equals',
        FilterTypeInterface::OPERATOR_IN => 'operator-equals',
        FilterTypeInterface::OPERATOR_NOT_IN => 'operator-not-equals',
        FilterTypeInterface::OPERATOR_LESS_THAN => 'operator-less-than',
        FilterTypeInterface::OPERATOR_BEFORE => 'operator-less-than',
        FilterTypeInterface::OPERATOR_LESS_THAN_EQUAL => 'operator-less-or-equal',
        FilterTypeInterface::OPERATOR_GREATER_THAN => 'operator-greater-than',
        FilterTypeInterface::OPERATOR_AFTER => 'operator-greater-than',
        FilterTypeInterface::OPERATOR_GREATER_THAN_EQUAL => 'operator-greater-or-equal',
        FilterTypeInterface::OPERATOR_INTERVAL => 'operator-between',
        FilterTypeInterface::OPERATOR_CONTAINS => 'operator-contains',
        FilterTypeInterface::OPERATOR_DOES_NOT_CONTAIN => 'operator-contains',
        FilterTypeInterface::OPERATOR_STARTS_WITH => 'operator-starts-with',
        FilterTypeInterface::OPERATOR_ENDS_WITH => 'operator-ends-with',
        FilterTypeInterface::OPERATOR_IS_NULL => 'operator-empty',
        FilterTypeInterface::OPERATOR_IS_NOT_NULL => 'operator-not-empty',
    ];

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $entityManager,
        private readonly Formatter $formatter,
        private readonly Environment $twig,
    ) {
    }

    /**
     * @param array<string, FilterTypeInterface> $filters the filters the user may see, with their data (FilterFactory)
     *
     * @return array<string, mixed> the props of the Filter:Bar
     */
    public function build(FilterSetInterface $filterset, array $filters, BrickView $view): array
    {
        $setId = $filterset->getId();
        $config = $view->getConfig();
        $domain = $config['translation_domain'] ?? null;

        $items = [];
        foreach ($filters as $filter) {
            $items[] = $this->item($setId, $filter, $view, $domain);
        }

        $path = $this->requestStack->getCurrentRequest()?->getPathInfo() ?? '';

        return [
            'id' => 'crudit-filters-' . $setId,
            'label' => $this->translator->trans('crudit.filter.region', ['%title%' => (string) ($config['title'] ?? '')], 'LleCruditBundle'),
            'filters' => $items,
            'resetUrl' => $path . '?' . $setId . '_reset=1',
            'applyName' => $setId . '_filter',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function item(string $setId, FilterTypeInterface $filter, BrickView $view, ?string $domain): array
    {
        $data = $filter instanceof AbstractFilterType ? ($filter->getData() ?? []) : [];
        $name = 'filter_' . $setId . '_' . $filter->getId();
        $label = $filter instanceof AbstractFilterType ? $this->translator->trans($filter->getLabel(), [], $domain) : $filter->getId();
        $operators = $this->operators($filter);
        $first = $operators[0]['value'] ?? null;
        $operator = (string) ($data['op'] ?? $first);
        $value = $data['value'] ?? null;
        $value = is_scalar($value) ? (string) $value : null;
        $to = isset($data['to']) && is_scalar($data['to']) ? (string) $data['to'] : null;

        [$type] = $this->type($filter) ?? ['legacy'];

        $item = [
            'id' => $filter->getId(),
            'label' => $label,
            'name' => $name,
            'type' => $type,
            'operators' => $operators,
            'operator' => $operator,
            'value' => $value,
            'to' => $to,
            'hidden' => $filter instanceof AbstractFilterType && $filter->isHidden(),
            'primary' => $filter instanceof AbstractFilterType && $filter->isPrimary(),
        ];

        $choices = [];
        if ($type === 'choice') {
            $choices = $this->choices($filter, $value, $domain);
            $item['choices'] = $choices;
        }
        $item['active'] = $this->isActive($type, $operator, $value, $to);
        $item['summary'] = $item['active'] ? $this->summary($type, $operators, $operator, $first, $value, $to, $choices) : '';

        if ($type === 'legacy' || $this->isCustomized($filter)) {
            $item['type'] = 'legacy';
            $item['template'] = $filter->getTemplate();
            // The variables of the 1.x, and the ones the base template of the filter needs to draw its frame.
            $item['context'] = [
                'id' => $name,
                'data' => $data,
                'filter' => $filter,
                'filtertype' => $filter,
                'view' => $view,
                'operators' => $operators,
                'operator' => $operator,
                'value' => $value,
                'to' => $to,
                'choices' => $choices,
            ];
            if ($item['summary'] === '' && $item['active']) {
                $item['summary'] = (string) $value;
            }
        }

        return $item;
    }

    /**
     * The type of the field and the family of the default template of a type of CruditBundle (or of a subclass).
     *
     * @return array{string, string}|null
     */
    private function type(FilterTypeInterface $filter): ?array
    {
        foreach (self::TYPES as [$class, $type, $family]) {
            if ($filter instanceof $class) {
                return [$type, $family];
            }
        }

        return null;
    }

    /**
     * True when the project brings its own template: set on the type, or a file that replaces the one of the bundle.
     */
    private function isCustomized(FilterTypeInterface $filter): bool
    {
        $family = ($this->type($filter) ?? [null, null])[1];
        if ($family === null) {
            return true;
        }
        $defaults = ['@LleCrudit/filter/type/' . $family . '_filter.html.twig'];
        if ($family === 'tree') {
            $defaults[] = '@LleCrudit/filter/type/entity_filter.html.twig';
        }
        $template = $filter->getTemplate();
        if (!in_array($template, $defaults, true)) {
            return true;
        }
        try {
            $path = $this->twig->getLoader()->getSourceContext($template)->getPath();
        } catch (LoaderError) {
            return true;
        }

        // templates/bundles/LleCruditBundle/…: the project replaces the template of the bundle.
        return str_contains(str_replace('\\', '/', $path), '/bundles/LleCruditBundle/');
    }

    /**
     * @return list<array{value: string, label: string, icon: string, noValue: bool}>
     */
    private function operators(FilterTypeInterface $filter): array
    {
        $operators = [];
        foreach (array_keys($filter->getOperators()) as $operator) {
            $operator = (string) $operator;
            $operators[] = [
                'value' => $operator,
                'label' => $this->translator->trans('crudit.filter.operator.' . $operator, [], 'LleCruditBundle'),
                'icon' => self::ICONS[$operator] ?? 'operator-equals',
                'noValue' => in_array($operator, [FilterTypeInterface::OPERATOR_IS_NULL, FilterTypeInterface::OPERATOR_IS_NOT_NULL], true),
            ];
        }

        return $operators;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function choices(FilterTypeInterface $filter, ?string $value, ?string $domain): array
    {
        $choices = [];
        if ($filter instanceof WorkflowFilterType) {
            foreach ($filter->getChoices() as $place) {
                $choices[] = ['value' => (string) $place, 'label' => $this->translator->trans('wf.state.' . $place)];
            }
        } elseif ($filter instanceof EntityFilterType) {
            $choices = $this->entityChoices($filter, $value);
        } elseif ($filter instanceof ChoiceFilterType) {
            foreach ($filter->getChoices() as $label => $choice) {
                $choices[] = ['value' => (string) $choice, 'label' => $this->translator->trans((string) $label, [], $domain)];
            }
        }

        return $choices;
    }

    /**
     * The first MAX_CHOICES entities by label, and the ones chosen even when they are beyond.
     *
     * @return list<array{value: string, label: string}>
     */
    private function entityChoices(EntityFilterType $filter, ?string $value): array
    {
        /** @var class-string $class */
        $class = $filter->getEntityClass();
        $choices = [];
        foreach ($this->entityManager->getRepository($class)->findBy([], null, self::MAX_CHOICES) as $entity) {
            $choices[$this->entityId($entity)] = $this->entityLabel($entity);
        }
        foreach (array_filter(explode(',', (string) $value), static fn (string $id): bool => $id !== '') as $id) {
            if (!isset($choices[$id]) && ($entity = $this->entityManager->find($class, $id)) !== null) {
                $choices[$id] = $this->entityLabel($entity);
            }
        }
        asort($choices, SORT_NATURAL | SORT_FLAG_CASE);

        $list = [];
        foreach ($choices as $id => $label) {
            $list[] = ['value' => (string) $id, 'label' => $label];
        }

        return $list;
    }

    private function entityId(object $entity): string
    {
        $ids = $this->entityManager->getClassMetadata($entity::class)->getIdentifierValues($entity);

        return (string) (array_values($ids)[0] ?? '');
    }

    private function entityLabel(object $entity): string
    {
        return $entity instanceof \Stringable ? (string) $entity : '#' . $this->entityId($entity);
    }

    private function isActive(string $type, string $operator, ?string $value, ?string $to): bool
    {
        if (in_array($operator, [FilterTypeInterface::OPERATOR_IS_NULL, FilterTypeInterface::OPERATOR_IS_NOT_NULL], true)) {
            return true;
        }
        if ($type === 'boolean') {
            return in_array($value, ['true', 'false'], true);
        }

        return ($value !== null && $value !== '') || ($to !== null && $to !== '');
    }

    /**
     * The value in words, for the chip and the button: "boiler", "Planned, Done", "Not equal to boiler".
     *
     * @param list<array{value: string, label: string, icon: string, noValue: bool}> $operators
     * @param list<array{value: string, label: string}> $choices
     */
    private function summary(string $type, array $operators, string $operator, ?string $first, ?string $value, ?string $to, array $choices): string
    {
        $label = '';
        foreach ($operators as $candidate) {
            if ($candidate['value'] === $operator) {
                $label = $candidate['label'];
            }
        }
        if (in_array($operator, [FilterTypeInterface::OPERATOR_IS_NULL, FilterTypeInterface::OPERATOR_IS_NOT_NULL], true)) {
            return $label;
        }
        // The condition is told when it is not the first of the list (the one assumed).
        $prefix = ($operator !== $first && $label !== '') ? $label . ' ' : '';

        $text = match ($type) {
            'boolean' => $this->translator->trans($value === 'true' ? 'crudit.boolean.yes' : 'crudit.boolean.no', [], 'LleCruditBundle'),
            'choice' => implode(', ', array_map(
                static function (string $id) use ($choices): string {
                    foreach ($choices as $choice) {
                        if ($choice['value'] === $id) {
                            return $choice['label'];
                        }
                    }

                    return $id;
                },
                array_filter(explode(',', (string) $value), static fn (string $id): bool => $id !== ''),
            )),
            'date' => $this->formatter->date($value),
            'datetime' => $this->formatter->datetime($value),
            'period' => $this->period($value, $to),
            'range' => $this->range($value, $to),
            default => (string) $value,
        };

        return $type === 'period' || $type === 'range' ? $text : $prefix . $text;
    }

    private function period(?string $from, ?string $to): string
    {
        $ui = 'LleCruditUiBundle';
        if ($from && $to) {
            return $this->translator->trans('crudit_ui.filter.period.range', ['%from%' => $this->formatter->date($from), '%to%' => $this->formatter->date($to)], $ui);
        }
        if ($from) {
            return $this->translator->trans('crudit_ui.filter.period.since', ['%date%' => $this->formatter->date($from)], $ui);
        }

        return $this->translator->trans('crudit_ui.filter.period.until', ['%date%' => $this->formatter->date((string) $to)], $ui);
    }

    private function range(?string $min, ?string $max): string
    {
        if ($min !== null && $min !== '' && $max !== null && $max !== '') {
            return $min . ' – ' . $max;
        }

        return $min !== null && $min !== '' ? '≥ ' . $min : '≤ ' . $max;
    }
}
