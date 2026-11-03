<?php

declare(strict_types=1);

namespace phpunit\Unit\Workflow;

use Lle\CruditBundle\Workflow\WorkflowStates;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Workflow\Definition;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\MarkingStore\MethodMarkingStore;
use Symfony\Component\Workflow\Metadata\InMemoryMetadataStore;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\SupportStrategy\InstanceOfSupportStrategy;
use Symfony\Component\Workflow\Transition;

/**
 * States and transitions of an element: the tone of a place (metadata `tone`, neutral by default), the transitions
 * allowed by the guards and by the role ROLE_<WORKFLOW>_WF_<TRANSITION> (asked with the element), the state they lead
 * to, `danger`; an element without state, read without being marked.
 */
class WorkflowStatesTest extends TestCase
{
    private EventDispatcher $dispatcher;

    /** @var list<array{mixed, mixed}> the role and the subject of each isGranted() */
    private array $asked = [];

    protected function setUp(): void
    {
        $this->dispatcher = new EventDispatcher();
    }

    public function testStateToneAndTransitions(): void
    {
        $ticket = new WorkflowTicket('open');
        $view = $this->states(['ROLE_TICKET_WF_START', 'ROLE_TICKET_WF_CANCEL'])->view($ticket);

        self::assertSame('ticket', $view->name);
        self::assertSame('Ticket T-1', $view->entityLabel);
        self::assertCount(1, $view->states);
        self::assertSame(['open', 'info'], [$view->states[0]->place, $view->states[0]->tone]);
        // A place without a tone is neutral
        self::assertSame('neutral', $view->transitions[0]->to->tone);

        self::assertSame(['start', 'cancel'], array_map(fn ($transition) => $transition->name, $view->transitions));
        self::assertSame(['in_progress', 'neutral', false], [$view->transitions[0]->to->place, $view->transitions[0]->to->tone, $view->transitions[0]->danger]);
        self::assertSame(['cancelled', 'red', true], [$view->transitions[1]->to->place, $view->transitions[1]->to->tone, $view->transitions[1]->danger]);
    }

    public function testATransitionWithoutItsRoleOrRefusedByAGuardIsHidden(): void
    {
        $ticket = new WorkflowTicket('open');
        self::assertSame(['start'], array_map(fn ($transition) => $transition->name, $this->states(['ROLE_TICKET_WF_START'])->view($ticket)->transitions));

        $this->dispatcher->addListener('workflow.ticket.guard.start', fn (GuardEvent $event) => $event->setBlocked(true));
        self::assertSame([], $this->states(['ROLE_TICKET_WF_START'])->view($ticket)->transitions);
    }

    public function testATransitionFromSeveralPlacesIsOfferedOnce(): void
    {
        $view = $this->states(['ROLE_TICKET_WF_CANCEL'])->view(new WorkflowTicket('in_progress'));

        self::assertSame(['cancel'], array_map(fn ($transition) => $transition->name, $view->transitions));
    }

    public function testTheWorkflowNamedOrTheFirstOne(): void
    {
        $states = $this->states([]);

        self::assertSame('ticket', $states->workflow(new WorkflowTicket('open'), 'ticket')->getName());
        self::assertSame('ticket', $states->workflow(new WorkflowTicket('open'), '')->getName());

        $this->expectException(\InvalidArgumentException::class);
        $states->workflow(new \stdClass());
    }

    public function testTheRoleIsAskedWithTheElement(): void
    {
        $ticket = new WorkflowTicket('open');
        $states = $this->states(['ROLE_TICKET_WF_START', 'ROLE_TICKET_WF_CANCEL']);
        $states->view($ticket);
        $states->isGranted('ticket', 'start', $ticket);

        self::assertSame(
            [['ROLE_TICKET_WF_START', $ticket], ['ROLE_TICKET_WF_CANCEL', $ticket], ['ROLE_TICKET_WF_START', $ticket]],
            $this->asked,
        );
    }

    public function testAnElementWithoutStateIsNotMarked(): void
    {
        $entered = false;
        $this->dispatcher->addListener('workflow.ticket.entered', function () use (&$entered): void {
            $entered = true;
        });
        $ticket = new WorkflowTicket(null);
        $states = $this->states(['ROLE_TICKET_WF_START', 'ROLE_TICKET_WF_CANCEL']);

        $view = $states->view($ticket);

        // No state, no transition; Workflow::getMarking() would have put it in "open" and dispatched "entered"
        self::assertSame([[], []], [$view->states, $view->transitions]);
        self::assertSame([], $states->places($states->workflow($ticket), $ticket));
        self::assertNull($ticket->getStatus());
        self::assertFalse($entered);
        self::assertSame([], $this->asked);
    }

    public function testTheStatesOnlyWithoutTransitions(): void
    {
        $view = $this->states(['ROLE_TICKET_WF_START'])->view(new WorkflowTicket('open'), null, false);

        self::assertSame(['open'], array_map(fn ($state) => $state->place, $view->states));
        self::assertSame([], $view->transitions);
        // Nothing asked to the voters
        self::assertSame([], $this->asked);
    }

    public function testRole(): void
    {
        self::assertSame('ROLE_TICKET_WF_SEND_BACK', WorkflowStates::role('ticket', 'send-back'));
    }

    /**
     * @param list<string> $roles
     */
    private function states(array $roles): WorkflowStates
    {
        $transitions = [
            new Transition('start', 'open', 'in_progress'),
            new Transition('cancel', 'open', 'cancelled'),
            new Transition('cancel', 'in_progress', 'cancelled'),
        ];
        /** @var \SplObjectStorage<Transition, array> $transitionsMetadata */
        $transitionsMetadata = new \SplObjectStorage();
        $transitionsMetadata[$transitions[1]] = ['danger' => true];
        $transitionsMetadata[$transitions[2]] = ['danger' => true];
        $definition = new Definition(
            ['open', 'in_progress', 'cancelled'],
            $transitions,
            'open',
            new InMemoryMetadataStore([], ['open' => ['tone' => 'info'], 'cancelled' => ['tone' => 'red']], $transitionsMetadata),
        );

        $registry = new Registry();
        $registry->addWorkflow(
            new StateMachine($definition, new MethodMarkingStore(true, 'status'), $this->dispatcher, 'ticket'),
            new InstanceOfSupportStrategy(WorkflowTicket::class),
        );

        $authorizationChecker = $this->createStub(AuthorizationCheckerInterface::class);
        $authorizationChecker->method('isGranted')->willReturnCallback(function (mixed $role, mixed $subject = null) use ($roles): bool {
            $this->asked[] = [$role, $subject];

            return in_array($role, $roles, true);
        });

        return new WorkflowStates($registry, $authorizationChecker);
    }
}

class WorkflowTicket implements \Stringable
{
    public function __construct(private ?string $status)
    {
    }

    public function getId(): int
    {
        return 1;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    public function __toString(): string
    {
        return 'Ticket T-1';
    }
}
