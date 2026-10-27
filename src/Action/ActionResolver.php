<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Action;

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
 * - a rule of the business (setDisabledReason(), canEdit(), canDelete()): always shown, disabled with its reason;
 *   an action of a project that overrides isDisabled() (1.x, deprecated) is disabled by it, with the reason of its
 *   getTitle() if overridden too, else a generic one.
 */
class ActionResolver
{
    /** @var array<class-string, array{isDisabled: bool, getTitle: bool}> the methods of 1.x overridden, by class */
    private array $overrides = [];

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

        $reason = $this->overrides($action)['isDisabled'] ? $this->legacyReason($action) : $action->getDisabledReason();

        return $reason === null ? ActionState::enabled() : ActionState::disabledBy($reason);
    }

    /**
     * The reason of an action that overrides isDisabled() (1.x): its getTitle() if overridden, else a generic one.
     */
    protected function legacyReason(AbstractAction $action): ?string
    {
        if (!$action->isDisabled()) {
            return null;
        }

        return $action->getDisabledReason()
            ?? ($this->overrides($action)['getTitle'] ? $action->getTitle() : ActionState::REASON_UNAVAILABLE);
    }

    /**
     * @return array{isDisabled: bool, getTitle: bool} the methods of 1.x that the class of the action overrides
     *                                                  outside the bundle (a deprecation, once per class)
     */
    private function overrides(AbstractAction $action): array
    {
        return $this->overrides[$action::class] ??= [
            'isDisabled' => $this->overridden($action, 'isDisabled'),
            'getTitle' => $this->overridden($action, 'getTitle'),
        ];
    }

    private function overridden(AbstractAction $action, string $method): bool
    {
        $declaring = (new \ReflectionMethod($action, $method))->getDeclaringClass();
        if (str_starts_with($declaring->getName(), 'Lle\\CruditBundle\\') && !$declaring->isAnonymous()) {
            return false;
        }
        trigger_deprecation(
            '2lenet/crudit-bundle',
            '2.0',
            'Overriding %s::%s() is deprecated, use setDisabledReason() (or override getDisabledReason()).',
            $declaring->getName(),
            $method,
        );

        return true;
    }

    protected function isGranted(AbstractAction $action, ?object $resource): bool
    {
        // The way back to the list by the history of the navigation (LinksFactory) is not checked, as before 2.0;
        // without it, the role of the list is
        if ($action->isBack() && $action->getUrl() !== null) {
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
