<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Action;

use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Dto\Action\AbstractAction;
use Lle\CruditBundle\Dto\Action\DeleteAction;
use Lle\CruditBundle\Dto\Action\ItemAction;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Attribute\AsTwigFunction;

/**
 * What an action is on a page (decision of 20/10: hideIfDisabled is kept for the actions of context):
 * - displayIf (or displayIfByRequest) false: hidden, the action has no sense here;
 * - a right refused (the role of the action or of its path): hidden with hideIfDisabled (the action, else the
 *   configuration hide_if_disabled, delete_hide_if_disabled for a deletion), else disabled with a generic reason;
 * - a rule of the business (setDisabledReason(), canEdit(), canDelete()): always shown, disabled with its reason.
 */
class ActionResolver
{
    public function __construct(
        protected AuthorizationCheckerInterface $authorizationChecker,
        protected bool $hideIfDisabled = true,
        protected ?bool $deleteHideIfDisabled = null,
    ) {
    }

    #[AsTwigFunction('crudit_action_state')]
    public function resolve(AbstractAction $action, ?object $resource = null, ?Request $request = null): ActionState
    {
        if ($action instanceof ItemAction && $resource !== null) {
            $action->setResource($resource);
        }

        if (!$action->isDisplayed($resource) || !$action->isDisplayedByRequest($request)) {
            return ActionState::hidden();
        }

        if (!$this->isGranted($action, $resource)) {
            return $this->hidesWhenForbidden($action)
                ? ActionState::hidden()
                : ActionState::disabledBy(ActionState::REASON_FORBIDDEN);
        }

        $reason = $action->getDisabledReason();

        return $reason === null ? ActionState::enabled() : ActionState::disabledBy($reason);
    }

    protected function isGranted(AbstractAction $action, ?object $resource): bool
    {
        // The way back to the list is not checked (as before 2.0)
        if ($action->getLabel() === CrudConfigInterface::LABEL_ACTION_LIST) {
            return true;
        }

        foreach ([$action->getPathRole(), $action->getRole()] as $role) {
            if ($role !== null && !$this->authorizationChecker->isGranted($role, $resource)) {
                return false;
            }
        }

        return true;
    }

    protected function hidesWhenForbidden(AbstractAction $action): bool
    {
        if ($action->getHideIfDisabled() !== null) {
            return $action->getHideIfDisabled();
        }

        if ($action instanceof DeleteAction && $this->deleteHideIfDisabled !== null) {
            return $this->deleteHideIfDisabled;
        }

        return $this->hideIfDisabled;
    }
}
