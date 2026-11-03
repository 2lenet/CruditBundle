<?php

declare(strict_types=1);

namespace phpunit\Unit\Workflow;

use Doctrine\ORM\EntityManagerInterface;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Contracts\DatasourceInterface;
use Lle\CruditBundle\Datasource\AbstractDoctrineDatasource;
use Lle\CruditBundle\Workflow\WorkflowCounter;
use Lle\CruditBundle\Workflow\WorkflowStates;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Workflow\Definition;
use Symfony\Component\Workflow\MarkingStore\MethodMarkingStore;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\SupportStrategy\InstanceOfSupportStrategy;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\Workflow;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * The property of the marking is read by reflection on MethodMarkingStore (private properties of Symfony): this test
 * fails if Symfony renames them. Only a state_machine whose marking is one place in a column can be counted. A CRUD
 * whose datasource cannot be counted is an error, never the whole table in silence.
 */
class WorkflowCounterTest extends TestCase
{
    public function testThePropertyOfAStateMachine(): void
    {
        $workflow = new StateMachine($this->definition(), new MethodMarkingStore(true, 'status'), name: 'ticket');

        self::assertSame('status', $this->markingProperty($workflow));
    }

    public function testAWorkflowCannotBeCounted(): void
    {
        $workflow = new Workflow($this->definition(), new MethodMarkingStore(false, 'places'), name: 'ticket');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The workflow "ticket" cannot be counted');
        $this->markingProperty($workflow);
    }

    public function testACrudWhoseDatasourceIsNotDoctrine(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The datasource of the CRUD "TICKET" (' . CounterTicket::class . ') cannot be counted for "' . CounterTicket::class . '"');
        $this->counter()->count(CounterTicket::class, crud: $this->crud($this->createStub(DatasourceInterface::class), CounterTicket::class));
    }

    public function testACrudOfAnotherEntity(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('(' . \stdClass::class . ') cannot be counted for "' . CounterTicket::class . '"');
        $this->counter()->count(CounterTicket::class, crud: $this->crud($this->createStub(AbstractDoctrineDatasource::class), \stdClass::class));
    }

    private function crud(DatasourceInterface $datasource, string $className): CrudConfigInterface
    {
        if ($datasource instanceof \PHPUnit\Framework\MockObject\Stub) {
            $datasource->method('getClassName')->willReturn($className);
        }
        $crud = $this->createStub(CrudConfigInterface::class);
        $crud->method('getDatasource')->willReturn($datasource);
        $crud->method('getName')->willReturn('TICKET');

        return $crud;
    }

    private function counter(): WorkflowCounter
    {
        $registry = new Registry();
        $registry->addWorkflow(
            new StateMachine($this->definition(), new MethodMarkingStore(true, 'status'), name: 'ticket'),
            new InstanceOfSupportStrategy(CounterTicket::class),
        );

        return new WorkflowCounter(
            $this->createStub(EntityManagerInterface::class),
            new WorkflowStates($registry, $this->createStub(AuthorizationCheckerInterface::class)),
            $this->createStub(UrlGeneratorInterface::class),
        );
    }

    private function definition(): Definition
    {
        return new Definition(['open', 'closed'], [new Transition('close', 'open', 'closed')]);
    }

    private function markingProperty(WorkflowInterface $workflow): string
    {
        $counter = new WorkflowCounter(
            $this->createStub(EntityManagerInterface::class),
            new WorkflowStates(new Registry(), $this->createStub(AuthorizationCheckerInterface::class)),
            $this->createStub(UrlGeneratorInterface::class),
        );

        /** @var string */
        return (new \ReflectionMethod($counter, 'markingProperty'))->invoke($counter, $workflow);
    }
}

class CounterTicket
{
    public ?string $status = null;
}
