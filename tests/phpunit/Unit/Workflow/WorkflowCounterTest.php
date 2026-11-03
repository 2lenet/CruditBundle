<?php

declare(strict_types=1);

namespace phpunit\Unit\Workflow;

use Doctrine\ORM\EntityManagerInterface;
use Lle\CruditBundle\Workflow\WorkflowCounter;
use Lle\CruditBundle\Workflow\WorkflowStates;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Workflow\Definition;
use Symfony\Component\Workflow\MarkingStore\MethodMarkingStore;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\Workflow;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * The property of the marking is read by reflection on MethodMarkingStore (private properties of Symfony): this test
 * fails if Symfony renames them. Only a state_machine whose marking is one place in a column can be counted.
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
