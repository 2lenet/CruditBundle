<?php

namespace phpunit\Unit\Controller;

use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Controller\AbstractCrudController;
use Lle\CruditBundle\Controller\TraitCrudController;
use Lle\CruditBundle\Datasource\AbstractDoctrineDatasource;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Workflow\Definition;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\SupportStrategy\InstanceOfSupportStrategy;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\WorkflowInterface;

class WorkflowTransitionTest extends TestCase
{
    private \stdClass $item;

    private AbstractDoctrineDatasource&MockObject $datasource;

    private WorkflowInterface&MockObject $orderWorkflow;

    private WorkflowInterface&MockObject $billingWorkflow;

    private Session $session;

    /** @var array<array{0: mixed, 1: mixed}> */
    private array $grantedChecks = [];

    protected function setUp(): void
    {
        $this->item = new \stdClass();

        $this->datasource = $this->createMock(AbstractDoctrineDatasource::class);
        $this->datasource->method('get')->willReturnCallback(fn($id) => $id === '1' ? $this->item : null);

        $this->orderWorkflow = $this->createMock(WorkflowInterface::class);
        $this->orderWorkflow->method('getName')->willReturn('order');
        $this->orderWorkflow->method('getDefinition')->willReturn(
            new Definition(['draft', 'validated'], [new Transition('to-validate', 'draft', 'validated')])
        );
        $this->billingWorkflow = $this->createMock(WorkflowInterface::class);
        $this->billingWorkflow->method('getName')->willReturn('billing');
        $this->billingWorkflow->method('getDefinition')->willReturn(
            new Definition(['unpaid', 'paid'], [new Transition('to-paid', 'unpaid', 'paid')])
        );

        $this->session = new Session(new MockArraySessionStorage());
    }

    public function testTransitionIsAppliedOnTheNamedWorkflowOnly(): void
    {
        $this->orderWorkflow->method('can')->willReturn(true);
        $this->billingWorkflow->method('can')->willReturn(true);
        $this->orderWorkflow->expects(self::once())->method('apply')->with($this->item, 'to-validate');
        $this->billingWorkflow->expects(self::never())->method('apply');
        $this->datasource->expects(self::once())->method('save')->with($this->item);

        $response = $this->transition(['name' => 'order', 'transition' => 'to-validate'], ['ROLE_ORDER_WF_TO_VALIDATE']);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame([['ROLE_ORDER_WF_TO_VALIDATE', null]], $this->grantedChecks);
    }

    public function testRoleOfAnotherWorkflowIsRefused(): void
    {
        $this->orderWorkflow->expects(self::never())->method('apply');
        $this->billingWorkflow->expects(self::never())->method('apply');
        $this->datasource->expects(self::never())->method('save');

        $this->expectException(AccessDeniedException::class);

        $this->transition(['name' => 'billing', 'transition' => 'to-validate'], ['ROLE_ORDER_WF_TO_VALIDATE']);
    }

    public function testAccessIsDeniedBeforeLoadingTheResource(): void
    {
        $this->datasource->expects(self::never())->method('get');

        $this->expectException(AccessDeniedException::class);

        $this->transition(['name' => 'order', 'transition' => 'to-validate'], []);
    }

    public function testTransitionOfAnotherWorkflowIsNotFound(): void
    {
        $this->orderWorkflow->method('can')->willReturn(true);
        $this->orderWorkflow->expects(self::never())->method('apply');
        $this->billingWorkflow->expects(self::never())->method('apply');
        $this->datasource->expects(self::never())->method('save');

        $this->expectException(NotFoundHttpException::class);

        // the user has the role of "order", but "to-paid" belongs to "billing"
        $this->transition(['name' => 'order', 'transition' => 'to-paid'], ['ROLE_ORDER_WF_TO_PAID']);
    }

    public function testMissingNameIsNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->transition(['transition' => 'to-validate'], ['ROLE_ORDER_WF_TO_VALIDATE']);
    }

    public function testMissingTransitionIsNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->transition(['name' => 'order'], ['ROLE_ORDER_WF_TO_VALIDATE']);
    }

    public function testUnknownWorkflowIsNotFound(): void
    {
        $this->datasource->expects(self::never())->method('save');

        $this->expectException(NotFoundHttpException::class);

        $this->transition(['name' => 'unknown', 'transition' => 'to-validate'], ['ROLE_UNKNOWN_WF_TO_VALIDATE']);
    }

    public function testImpossibleTransitionIsNotApplied(): void
    {
        $this->orderWorkflow->method('can')->willReturn(false);
        $this->orderWorkflow->expects(self::never())->method('apply');
        $this->datasource->expects(self::never())->method('save');

        $this->transition(['name' => 'order', 'transition' => 'to-validate'], ['ROLE_ORDER_WF_TO_VALIDATE']);

        self::assertSame(['crudit.flash.error.workflow_transition'], $this->session->getFlashBag()->get('danger'));
    }

    public function testResourceNotFoundRedirectsToIndex(): void
    {
        $this->datasource->expects(self::never())->method('save');

        $response = $this->transition(['name' => 'order', 'transition' => 'to-validate'], ['ROLE_ORDER_WF_TO_VALIDATE'], '2');

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/order', $response->getTargetUrl());
        self::assertSame(['crudit.flash.error.resource_not_found'], $this->session->getFlashBag()->get('danger'));
    }

    /**
     * @param array<string, string> $query
     * @param string[] $roles
     */
    private function transition(array $query, array $roles, string $id = '1'): RedirectResponse
    {
        $config = $this->createMock(CrudConfigInterface::class);
        $config->method('getName')->willReturn('ORDER');
        $config->method('getDatasource')->willReturn($this->datasource);
        $config->method('getRootRoute')->willReturn('app_crudit_order');

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->method('isGranted')->willReturnCallback(
            function (mixed $attribute, mixed $subject = null) use ($roles): bool {
                $this->grantedChecks[] = [$attribute, $subject];

                return in_array($attribute, $roles, true);
            }
        );

        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->method('generate')->willReturnMap([['app_crudit_order_index', [], UrlGeneratorInterface::ABSOLUTE_PATH, '/order']]);

        $request = new Request($query, [], ['id' => $id]);
        $request->setSession($this->session);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $container = new Container();
        $container->set('security.authorization_checker', $authorizationChecker);
        $container->set('router', $router);
        $container->set('request_stack', $requestStack);

        $registry = new Registry();
        $registry->addWorkflow($this->orderWorkflow, new InstanceOfSupportStrategy(\stdClass::class));
        $registry->addWorkflow($this->billingWorkflow, new InstanceOfSupportStrategy(\stdClass::class));

        $controller = new class ($config) extends AbstractCrudController {
            use TraitCrudController;

            public function __construct(CrudConfigInterface $config)
            {
                $this->config = $config;
            }
        };
        $controller->setContainer($container);

        $response = $controller->workflowTransition($request, $registry, $id);
        self::assertInstanceOf(RedirectResponse::class, $response);

        return $response;
    }
}
