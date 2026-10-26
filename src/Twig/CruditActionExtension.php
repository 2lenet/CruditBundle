<?php

namespace Lle\CruditBundle\Twig;

use Lle\CruditBundle\Action\ActionView;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Dto\Action\AbstractAction;
use Lle\CruditBundle\Dto\Action\DropdownAction;
use Lle\CruditBundle\Dto\Action\ItemAction;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CruditActionExtension extends AbstractExtension
{
    public function __construct(
        protected ?TranslatorInterface $translator = null,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('crudit_is_dropdown_action', [$this, 'isDropdownAction']),
            new TwigFunction('crudit_is_dropdown_group_name', [$this, 'isDropdownGroupName']),
            new TwigFunction('crudit_action_href', [$this, 'actionHref']),
            new TwigFunction('crudit_action_attributes', [$this, 'actionAttributes']),
        ];
    }

    public function isDropdownAction(AbstractAction $action): bool
    {
        return $action instanceof DropdownAction;
    }

    public function isDropdownGroupName(mixed $groupName): bool
    {
        return is_string($groupName);
    }

    /**
     * The address of the element of an action (the prop href of Button or MenuItem): none when it is a button, that
     * is disabled (a disabled link becomes a button), with a modal, a confirmation or a print.
     */
    public function actionHref(ActionView $item): ?string
    {
        $action = $item->action;
        if ($item->state->isDisabled() || $item->isPrint() || $action->getModal() !== null || $action->getConfirmModal()) {
            return null;
        }

        return $item->url;
    }

    /**
     * The attributes of the element of an action (Button, MenuItem), besides its props:
     * - its classes (setCssClass(), without the Bootstrap ones), its target, no referrer for the way back to the list;
     * - disabled: aria-disabled (the focus stays, the controller button of CruditUiBundle ignores the click), and
     *   aria-describedby of the element that holds its reason, if given;
     * - else how it opens: its modal (commandfor, command), the dialog of confirmation crudit-confirm with the address,
     *   the question that names the element, the label of the button and its colour (controller confirm), the print
     *   (controller print).
     *
     * @return array<string, string>
     */
    public function actionAttributes(ActionView $item, ?string $domain = null, ?string $describedBy = null): array
    {
        $action = $item->action;
        $attributes = array_filter([
            'class' => $action->getCssClass(),
            'target' => $action->getTarget(),
            // The way back to the list, an address of the history of the navigation
            'rel' => $action->getLabel() === CrudConfigInterface::LABEL_ACTION_LIST ? 'noreferrer' : null,
        ]);

        if ($item->state->isDisabled()) {
            return $attributes + array_filter(['aria-disabled' => 'true', 'aria-describedby' => $describedBy]);
        }

        if ($action->getModal() !== null) {
            $attributes += ['commandfor' => $item->getModalId(), 'command' => 'show-modal'];
        } elseif ($action->getConfirmModal()) {
            $attributes += [
                'commandfor' => 'crudit-confirm',
                'command' => 'show-modal',
                'data-confirm-link' => (string) $item->url,
                'data-confirm-text' => $this->confirmQuestion($action, $domain),
                'data-confirm-label' => $this->trans($action->getLabel(), [], $action->getTranslationDomain() ?? $domain),
            ] + (str_starts_with($item->getVariant(), 'danger') ? ['data-confirm-danger' => 'true'] : []);
        } elseif ($item->isPrint()) {
            $attributes += [
                'data-controller' => '2lenet--crudit-bundle--print',
                'data-action' => '2lenet--crudit-bundle--print#print',
            ];
        }

        return $attributes;
    }

    /**
     * The question of the confirmation, which names the element (decision of 20/10): the key modal.confirm.<label> of
     * the CRUD, given %entity%, else "<action> « <element> » ?" (crudit.modal.confirm.question).
     */
    protected function confirmQuestion(AbstractAction $action, ?string $domain): string
    {
        $resource = $action instanceof ItemAction ? $action->getResource() : null;
        $entity = $resource instanceof \Stringable ? (string) $resource : null;
        $key = 'modal.confirm.' . $action->getLabel();
        $question = $this->trans($key, ['%entity%' => (string) $entity], $domain);
        if ($question !== $key) {
            return $question;
        }

        $label = $this->trans($action->getLabel(), [], $action->getTranslationDomain() ?? $domain);

        return $entity === null || $entity === ''
            ? $this->trans('crudit.modal.confirm.question', ['%action%' => $label], 'LleCruditBundle')
            : $this->trans('crudit.modal.confirm.question_entity', ['%action%' => $label, '%entity%' => $entity], 'LleCruditBundle');
    }

    /**
     * @param array<string, string> $parameters
     */
    protected function trans(string $key, array $parameters, ?string $domain): string
    {
        return $this->translator?->trans($key, $parameters, $domain) ?? strtr($key, $parameters);
    }
}
