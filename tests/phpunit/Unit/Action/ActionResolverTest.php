<?php

declare(strict_types=1);

namespace phpunit\Unit\Action;

use Lle\CruditBundle\Action\ActionResolver;
use Lle\CruditBundle\Action\ActionState;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Dto\Action\AbstractAction;
use Lle\CruditBundle\Dto\Action\DeleteAction;
use Lle\CruditBundle\Dto\Action\DropdownAction;
use Lle\CruditBundle\Dto\Action\EditAction;
use Lle\CruditBundle\Dto\Action\ListAction;
use Lle\CruditBundle\Dto\Path;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * displayIf false: hidden; a right refused: hidden with hideIfDisabled, else disabled with a generic reason; a rule of
 * the business (canEdit, canDelete, setDisabledReason): always shown, disabled with its reason.
 */
class ActionResolverTest extends TestCase
{
    public function testUsable(): void
    {
        $state = $this->resolver()->resolve($this->action());

        self::assertTrue($state->visible);
        self::assertFalse($state->isDisabled());
        self::assertNull($state->reason);
    }

    public function testDisplayIfHides(): void
    {
        $action = $this->action()->setDisplayIf(static fn (): bool => false)->setHideIfDisabled(false);

        self::assertFalse($this->resolver()->resolve($action)->visible);
    }

    public function testRightRefusedIsHiddenByDefault(): void
    {
        $action = $this->action()->setRole('ROLE_NOBODY');

        self::assertFalse($this->resolver()->resolve($action)->visible);
    }

    public function testRightRefusedIsDisabledWithoutHideIfDisabled(): void
    {
        $state = $this->resolver(hideIfDisabled: false)->resolve($this->action()->setRole('ROLE_NOBODY'));

        self::assertTrue($state->visible);
        self::assertSame(ActionState::REASON_FORBIDDEN, $state->reason);
        self::assertSame('LleCruditBundle', $state->getReasonDomain());
    }

    public function testTheActionOverridesTheConfiguration(): void
    {
        $shown = $this->action()->setRole('ROLE_NOBODY')->setHideIfDisabled(false);
        $hidden = $this->action()->setRole('ROLE_NOBODY')->setHideIfDisabled(true);

        self::assertTrue($this->resolver()->resolve($shown)->visible);
        self::assertFalse($this->resolver(hideIfDisabled: false)->resolve($hidden)->visible);
    }

    public function testRoleOfThePathIsChecked(): void
    {
        $path = Path::new('app_client_new');
        $path->setRole('ROLE_NOBODY'); // @phpstan-ignore method.deprecated (still read, for the projects)
        $action = ListAction::new('action.add', $path);

        self::assertFalse($this->resolver()->resolve($action)->visible);
    }

    /**
     * Back by the history of the navigation (an url): not checked, as before 2.0; else the role of the list is.
     */
    public function testTheWayBackToTheList(): void
    {
        $back = static fn (): ListAction => ListAction::new(CrudConfigInterface::LABEL_ACTION_LIST, Path::new('app_client_index'))
            ->setRole('ROLE_NOBODY');

        self::assertTrue($this->resolver()->resolve($back()->setUrl('/client/?page=2'))->visible);
        self::assertFalse($this->resolver()->resolve($back())->visible);
    }

    public function testDeleteHasItsOwnConfiguration(): void
    {
        $delete = DeleteAction::new('action.delete', Path::new('app_client_delete'))->setRole('ROLE_NOBODY');

        self::assertTrue($this->resolver(deleteHideIfDisabled: false)->resolve($delete)->visible);
        self::assertFalse($this->resolver(deleteHideIfDisabled: null)->resolve($delete)->visible);
        self::assertTrue($this->resolver(hideIfDisabled: false, deleteHideIfDisabled: null)->resolve($delete)->visible);
    }

    public function testReasonOfTheBusinessIsAlwaysShown(): void
    {
        $edit = EditAction::new('action.edit', Path::new('app_client_edit'))->setHideIfDisabled(true);
        $resource = new class () {
            public function canEdit(): string
            {
                return 'client.locked';
            }
        };

        $state = $this->resolver()->resolve($edit, $resource);

        self::assertTrue($state->visible);
        self::assertSame('client.locked', $state->reason);
        self::assertNull($state->getReasonDomain());
        self::assertSame($resource, $edit->getResource());
    }

    public function testCanDeleteFalseGivesAGenericReason(): void
    {
        $resource = new class () {
            public function canDelete(): bool
            {
                return false;
            }
        };

        $state = $this->resolver()->resolve(DeleteAction::new('action.delete', Path::new('app_client_delete')), $resource);

        self::assertSame(ActionState::REASON_UNAVAILABLE, $state->reason);
    }

    public function testCanEditTrueIsUsable(): void
    {
        $resource = new class () {
            public function canEdit(): bool
            {
                return true;
            }
        };

        self::assertFalse($this->resolver()->resolve(EditAction::new('action.edit', Path::new('app_client_edit')), $resource)->isDisabled());
    }

    public function testDisabledReasonGivenTheResource(): void
    {
        $action = $this->action()->setDisabledReason(static fn (mixed $resource): ?string => $resource === null ? null : 'no');

        self::assertFalse($this->resolver()->resolve($action)->isDisabled());
        self::assertSame('client.archived', $this->resolver()->resolve($this->action()->setDisabledReason('client.archived'))->reason);
    }

    public function testRightBeforeTheReason(): void
    {
        $action = $this->action()->setRole('ROLE_NOBODY')->setDisabledReason('client.archived');

        self::assertSame(ActionState::REASON_FORBIDDEN, $this->resolver(hideIfDisabled: false)->resolve($action)->reason);
    }

    public function testDropdownWithoutPath(): void
    {
        self::assertTrue($this->resolver()->resolve(DropdownAction::new('action.more', []))->visible);
    }

    private function action(): AbstractAction
    {
        return ListAction::new('action.archive', Path::new('app_client_archive'));
    }

    private function resolver(bool $hideIfDisabled = true, ?bool $deleteHideIfDisabled = null): ActionResolver
    {
        $checker = new class () implements AuthorizationCheckerInterface {
            public function isGranted(mixed $attribute, mixed $subject = null, mixed $accessDecision = null): bool
            {
                return $attribute !== 'ROLE_NOBODY';
            }
        };

        return new ActionResolver($checker, $hideIfDisabled, $deleteHideIfDisabled);
    }
}
