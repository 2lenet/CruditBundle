<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Builder;

use Lle\CruditBundle\Dto\Action\AbstractAction;
use Lle\CruditBundle\Dto\Action\DeleteAction;
use Lle\CruditBundle\Dto\Action\DropdownAction;
use Lle\CruditBundle\Dto\Action\EditAction;
use Lle\CruditBundle\Dto\Action\ItemAction;
use Lle\CruditBundle\Dto\BrickView;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\FieldView;
use Lle\CruditBundle\Dto\ResourceView;
use Lle\CruditBundle\Twig\CruditExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The data of the Table of CruditUiBundle (`LleCruditUi:Table`) for a list (ListBrick), from its view: columns, rows,
 * groups (ruptures), totals, sort, manual order and actions of the rows. The component knows nothing of Crudit, the
 * rights, the translation of the labels, the addresses and the rendering of the values are done here.
 *
 * Returned by `build()`:
 * - the props of the component: `id`, `caption`, `columns`, `rows` (without ruptures) or `groups`, `totals`, `sort`,
 *   `sortable`;
 * - `modals`: the modals of the actions `setModal()`, to include after the table (`{template, id, action, line}`): the
 *   button of the row opens them with `commandfor`.
 *
 * The action `EditAction` is the one shown as an icon, the others are in the menu "More actions" of the row.
 *
 * Left to the next lots: the selection and the bar of the batch actions (3.1.c), the pager (3.1.d), the filters (3.1.e).
 */
class TableViewBuilder
{
    /** Types of fields whose value is a number: aligned at the end of the cell. */
    private const NUMERIC_TYPES = ['number', 'integer', 'decimal', 'float', 'currency'];

    /** Names of the icons of CruditBundle 1.x (Font Awesome) that have another name in the catalogue of the design system. */
    private const ICONS = [
        'trash-alt' => 'delete',
        'trash' => 'delete',
        'pencil' => 'edit',
        'pencil-alt' => 'edit',
        'file-export' => 'download',
        'eye' => 'search',
        'arrow-circle-left' => 'back',
    ];

    private const TONES = ['danger', 'warning', 'success', 'info'];

    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker,
        private TranslatorInterface $translator,
        private UrlGeneratorInterface $urlGenerator,
        private RequestStack $requestStack,
        private CruditExtension $cruditExtension,
    ) {
    }

    public function build(BrickView $view): array
    {
        $config = $view->getConfig();
        $data = $view->getData();
        $domain = $config['translation_domain'] ?? null;
        $name = strtolower((string) $config['name']);

        $fields = array_values(array_filter($config['fields'], $this->isShown(...)));
        [$sortField, $sortDir] = $this->currentSort($config);

        $columns = [];
        foreach ($fields as $i => $field) {
            $columns[] = $this->column($field, $i === 0, $name, $domain, $sortField, $sortDir);
        }
        $columnIds = array_column($columns, 'id');

        $modals = [];
        $rows = [];
        $groups = [];
        $firstGroup = null;
        $secondGroup = null;
        foreach ($data['lines'] as $line) {
            $row = $this->row($line, $view, $name, $domain, $modals);

            if ($line->getRuptGroup1() !== null) {
                $break = $line->getRuptGroup1();
                $groups[] = [
                    'key' => $break->key,
                    'label' => $this->text($break->display),
                    'rows' => [],
                    'children' => [],
                    'subtotals' => $this->subtotals($data['grouped_totals'][$break->key] ?? [], $columnIds),
                ];
                $firstGroup = array_key_last($groups);
                $secondGroup = null;
            }
            if ($line->getRuptGroup2() !== null) {
                $break = $line->getRuptGroup2();
                if ($firstGroup === null) {
                    $groups[] = ['key' => $break->key, 'label' => $this->text($break->display), 'rows' => [], 'children' => []];
                    $firstGroup = array_key_last($groups);
                } else {
                    $groups[$firstGroup]['children'][] = ['key' => $break->key, 'label' => $this->text($break->display), 'rows' => []];
                    $secondGroup = array_key_last($groups[$firstGroup]['children']);
                }
            }

            if ($firstGroup === null) {
                $rows[] = $row;
            } elseif ($secondGroup !== null) {
                $groups[$firstGroup]['children'][$secondGroup]['rows'][] = $row;
            } else {
                $groups[$firstGroup]['rows'][] = $row;
            }
        }

        // A group with children has no rows of its own (the component shows one or the other).
        foreach ($groups as &$group) {
            if (($group['children'] ?? []) === []) {
                unset($group['children']);
            } else {
                unset($group['rows']);
            }
        }
        unset($group);

        return [
            'id' => $view->getId(),
            'caption' => $this->caption($config, $domain, $sortField, $sortDir),
            'columns' => $columns,
            'rows' => $rows,
            'groups' => $groups,
            'totals' => $this->totals($data['totals'] ?? [], $columnIds, $domain),
            'sort' => $sortField !== null ? ['column' => $this->columnOf($fields, $sortField), 'dir' => $sortDir] : null,
            'sortable' => $this->sortable($config),
            'modals' => $modals,
        ];
    }

    private function isShown(Field $field): bool
    {
        return ($field->getRuptGroup() === 0 || !$field->isRuptHideFromList())
            && ($field->getRole() === null || $this->authorizationChecker->isGranted($field->getRole()));
    }

    /** @return array{0: ?string, 1: string} the property of the sort in progress and its direction (`asc`, `desc`) */
    private function currentSort(array $config): array
    {
        $sort = ($config['datasource_params']->getSorts())[0] ?? null;
        $property = is_array($sort) ? ($sort[0] ?? null) : $sort;
        $dir = is_array($sort) ? strtolower((string) ($sort[1] ?? 'asc')) : 'asc';

        return [$property === null || $property === '' ? null : (string) $property, $dir === 'desc' ? 'desc' : 'asc'];
    }

    private function sortProperty(Field $field): string
    {
        return (string) ($field->getOptions()['sortProperty'] ?? $field->getName());
    }

    /** @param Field[] $fields */
    private function columnOf(array $fields, string $sortField): ?string
    {
        foreach ($fields as $field) {
            if ($this->sortProperty($field) === $sortField) {
                return $field->getId();
            }
        }

        return null;
    }

    private function column(Field $field, bool $primary, string $name, ?string $domain, ?string $sortField, string $sortDir): array
    {
        $property = $this->sortProperty($field);
        $sorted = $sortField === $property;
        $url = null;
        if ($field->isSortable()) {
            $query = array_merge($this->request()?->query->all() ?? [], [
                $name . '_sort' => $property,
                $name . '_sort_order' => $sorted && $sortDir === 'asc' ? 'desc' : 'asc',
            ]);
            $url = ($this->request()?->getPathInfo() ?? '') . '?' . http_build_query($query);
        }

        return [
            'id' => $field->getId(),
            'label' => $this->translator->trans($field->getLabel(), [], $domain),
            'sortUrl' => $url,
            'align' => in_array($field->getType(), self::NUMERIC_TYPES, true) ? 'end' : 'start',
            'primary' => $primary,
            'card' => true,
            'info' => $field->getInfo() ? $this->translator->trans($field->getInfo()) : null,
        ];
    }

    private function row(ResourceView $line, BrickView $view, string $name, ?string $domain, array &$modals): array
    {
        $config = $view->getConfig();
        $domId = 'list-' . $name . '-' . $line->getId();

        $cells = [];
        $label = null;
        $url = null;
        foreach ($line->getFields() as $fieldView) {
            $field = $fieldView->getField();
            if (!$this->isShown($field)) {
                continue;
            }
            $cellUrl = $this->fieldUrl($fieldView, $line);
            $html = (string) $fieldView->getValue();
            $cells[$field->getId()] = [
                'html' => $html,
                'url' => $cellUrl,
                'id' => $domId . '-' . strtolower($field->getId()) . '-' . strtolower(str_replace(['field.', 'label.'], '', $field->getLabel())),
            ];
            $label ??= $this->text($html) ?: null;
            $url ??= $cellUrl;
        }

        $resource = $line->getResource();

        return [
            'id' => (string) $line->getId(),
            'domId' => $domId,
            'label' => $label ?? '#' . $line->getId(),
            'url' => $url,
            'tone' => $this->tone($resource),
            'cells' => $cells,
            'actions' => $this->actions($config['actions'], $line, $domain, $modals),
        ];
    }

    private function fieldUrl(FieldView $fieldView, ResourceView $line): ?string
    {
        $path = $fieldView->getField()->getPath();
        if ($path === null) {
            return null;
        }

        return $this->urlGenerator->generate(
            $path->getRoute(),
            $path->getParams(['id' => $line->getLinkId($fieldView->getField()->getLinkId())])
        );
    }

    private function tone(object $resource): ?string
    {
        if (!method_exists($resource, 'getCssClass')) {
            return null;
        }
        $class = (string) $resource->getCssClass();
        foreach (self::TONES as $tone) {
            if (str_contains($class, $tone)) {
                return $tone;
            }
        }

        return null;
    }

    /**
     * @param AbstractAction[] $actions
     */
    private function actions(array $actions, ResourceView $line, ?string $domain, array &$modals): array
    {
        $built = [];
        foreach ($actions as $action) {
            if ($action instanceof DropdownAction) {
                foreach ($this->dropdown($action, $line, $domain, $modals) as $entry) {
                    $built[] = $entry;
                }
            } elseif ($item = $this->action($action, $line, $domain, $action instanceof EditAction && !$action->isDropdown(), $modals)) {
                $built[] = $item;
            }
        }

        return $built;
    }

    /** A dropdown action (`DropdownAction`): one group of the menu "More actions" per named group of its actions. */
    private function dropdown(DropdownAction $dropdown, ResourceView $line, ?string $domain, array &$modals): array
    {
        $disabled = $dropdown->isDisabled() || !$this->granted($dropdown->getRole(), $line);
        if ($disabled && $this->cruditExtension->hideIfDisabled($dropdown)) {
            return [];
        }

        $entries = [];
        foreach ($dropdown->getActions() as $groupName => $group) {
            $items = [];
            foreach ($group as $action) {
                if ($item = $this->action($action, $line, $domain, false, $modals)) {
                    $items[] = $item;
                }
            }
            if ($items !== []) {
                $label = is_string($groupName) ? $groupName : $dropdown->getLabel();
                $entries[] = ['label' => $this->translator->trans($label, [], $domain), 'items' => $items];
            }
        }

        return $entries;
    }

    private function action(AbstractAction $action, ResourceView $line, ?string $domain, bool $visible, array &$modals): ?array
    {
        $resource = $line->getResource();
        if ($action instanceof ItemAction) {
            $action->setResource($resource);
        }

        $path = $action->getPath();
        $disabled = $action->isDisabled()
            || !($this->granted($path->getRole(), $line) && $this->granted($action->getRole(), $line));
        if (
            !$action->isDisplayed($resource)
            || !$action->isDisplayedByRequest($this->request())
            || ($disabled && $this->cruditExtension->hideIfDisabled($action))
        ) {
            return null;
        }

        $label = $this->translator->trans($action->getLabel(), [], $domain);
        $url = $action->getUrl() ?? $this->urlGenerator->generate($path->getRoute(), $path->getParams(['id' => $line->getId()]));

        $attr = [];
        if ($action->getTarget()) {
            $attr['target'] = $action->getTarget();
        }
        if ($action->getModal() !== null) {
            $id = 'modal_' . $action->getId() . $line->getId();
            $modals[] = ['template' => $action->getModal(), 'id' => $id, 'action' => $action, 'line' => $line];
            $attr += ['commandfor' => $id, 'command' => 'show-modal'];
            $url = null;
        } elseif ($action->getConfirmModal()) {
            $attr += [
                'data-confirm-link' => $url,
                'data-confirm-text' => $this->translator->trans('modal.confirm.' . $action->getLabel(), [], $domain),
            ];
            $url = null;
        }

        $reason = null;
        if ($disabled) {
            $title = $this->translator->trans($action->getTitle(), [], $domain);
            $reason = $title !== $label ? $title : $this->translator->trans('crudit.action.unavailable', [], 'LleCruditBundle');
            $url = null;
        }

        return [
            'label' => $label,
            'icon' => $this->icon($action),
            'url' => $url,
            'visible' => $visible,
            'tone' => $action instanceof DeleteAction ? 'danger' : null,
            'disabledReason' => $reason,
            'attr' => $attr,
        ];
    }

    private function granted(?string $role, ResourceView $line): bool
    {
        return $role === null || $this->authorizationChecker->isGranted($role, $line->getResource());
    }

    /** The logical name of the design system when the icon has one, else the name given (an Iconify name `set:name`). */
    private function icon(AbstractAction $action): ?string
    {
        $name = $action->getIcon()?->getIcon();

        return $name === null ? null : (self::ICONS[$name] ?? $name);
    }

    /**
     * @param FieldView[] $totals
     * @param string[]    $columnIds
     */
    private function totals(array $totals, array $columnIds, ?string $domain): array
    {
        $built = [];
        foreach ($totals as $total) {
            $field = $total->getField();
            // the first column of the totals row holds its label: a total aimed at it is shown under the table
            $aligned = in_array($field->getId(), $columnIds, true) && $field->getId() !== ($columnIds[0] ?? null);
            $built[] = $aligned
                ? ['column' => $field->getId(), 'html' => (string) $total->getValue()]
                : ['label' => $this->translator->trans($field->getLabel(), [], $domain), 'html' => (string) $total->getValue(), 'standalone' => true];
        }

        return $built;
    }

    /**
     * @param FieldView[] $totals
     * @param string[]    $columnIds
     */
    private function subtotals(array $totals, array $columnIds): array
    {
        $built = [];
        foreach ($totals as $total) {
            $id = $total->getField()->getId();
            if (in_array($id, $columnIds, true)) {
                $built[$id] = ['html' => (string) $total->getValue()];
            }
        }

        return $built;
    }

    private function caption(array $config, ?string $domain, ?string $sortField, string $sortDir): string
    {
        $title = $config['title'] ?? null;
        $parts = [$title ? $this->translator->trans($title, [], $domain) : (string) $config['name']];

        $column = null;
        foreach ($config['fields'] as $field) {
            if ($this->sortProperty($field) === $sortField) {
                $column = $this->translator->trans($field->getLabel(), [], $domain);
                break;
            }
        }
        if ($column !== null) {
            $parts[] = $this->translator->trans('crudit.table.sorted_' . $sortDir, ['%column%' => $column], 'LleCruditBundle');
        }

        $params = $config['datasource_params'];
        if ($params->getNbPages() > 1) {
            $parts[] = $this->translator->trans('crudit.table.page', [
                '%page%' => $params->getCurrentPage(),
                '%pages%' => $params->getNbPages(),
            ], 'LleCruditBundle');
        }

        return implode(', ', $parts);
    }

    private function sortable(array $config): bool|array
    {
        if (!$config['sortable'] || !$this->authorizationChecker->isGranted('ROLE_' . $config['name'] . '_EDIT')) {
            return false;
        }

        return ['url' => $this->urlGenerator->generate($config['sort_url']->getRoute(), $config['sort_url']->getParams())];
    }

    /** The text of a value rendered as HTML (label of a group, name of a row). */
    private function text(string $html): string
    {
        return trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5)) ?? '');
    }

    private function request(): ?Request
    {
        return $this->requestStack->getCurrentRequest();
    }
}
