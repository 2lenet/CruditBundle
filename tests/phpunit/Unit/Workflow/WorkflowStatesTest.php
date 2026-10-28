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
 * allowed by the guards and by the role ROLE_<WORKFLOW>_WF_<TRANSITION>, the state they lead to, `danger`.
 */
class WorkflowStatesTest extends TestCase
{
    private EventDispatcher $dispatcher;

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
        $authorizationChecker->method('isGranted')->willReturnCallback(fn (mixed $role) => in_array($role, $roles, true));

        return new WorkflowStates($registry, $authorizationChecker);
    }
}

class WorkflowTicket implements \Stringable
{
    public function __construct(private string $status)
    {
    }

    public function getId(): int
    {
        return 1;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function __toString(): string
    {
        return 'Ticket T-1';
    }
}
