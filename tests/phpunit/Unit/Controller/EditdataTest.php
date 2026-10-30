<?php

namespace phpunit\Unit\Controller;

use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Controller\AbstractCrudController;
use Lle\CruditBundle\Controller\TraitCrudController;
use Lle\CruditBundle\Datasource\AbstractDoctrineDatasource;
use Lle\CruditBundle\Dto\Field\Field;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class EditdataTest extends TestCase
{
    private \stdClass $item;

    private AbstractDoctrineDatasource&MockObject $datasource;

    private LoggerInterface&MockObject $logger;

    /** @var array<array{0: mixed, 1: mixed}> */
    private array $grantedChecks = [];

    protected function setUp(): void
    {
        $this->item = new \stdClass();
        $this->item->locked = false;

        $this->datasource = $this->createMock(AbstractDoctrineDatasource::class);
        $this->datasource->method('get')->willReturn($this->item);
        $this->datasource->method('save')->willReturn(true);

        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testDeclaredFieldIsSaved(): void
    {
        $this->datasource->expects(self::once())->method('fillFromData')
            ->with($this->item, ['id' => '1', 'label' => 'new label'], []);
        $this->logger->expects(self::never())->method('warning');

        $response = $this->editdata([Field::new('label')->setEditInPlace(true)], ['id' => '1', 'label' => 'new label']);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testUndeclaredFieldIsRefused(): void
    {
        $this->datasource->expects(self::never())->method('fillFromData');
        $this->logger->expects(self::once())->method('warning');

        $response = $this->editdata(
            [Field::new('label')->setEditInPlace(true), Field::new('roles')],
            ['label' => 'new label', 'roles' => ['ROLE_ADMIN']]
        );

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testCascadeFieldMatchesOnId(): void
    {
        $this->datasource->expects(self::once())->method('fillFromData');

        $response = $this->editdata([Field::new('address.city')->setEditInPlace(true)], ['address_city' => 'Lille']);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testEditRoleIsCheckedOnTheResource(): void
    {
        $this->datasource->expects(self::never())->method('fillFromData');

        $response = $this->editdata(
            [Field::new('label')->setEditable('app_crudit_order_editdata', 'ROLE_ORDER_LABEL_EIP')],
            ['label' => 'new label']
        );

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertContains(['ROLE_ORDER_LABEL_EIP', $this->item], $this->grantedChecks);
    }

    public function testEditRoleIsGranted(): void
    {
        $this->datasource->expects(self::once())->method('fillFromData');

        $response = $this->editdata(
            [Field::new('label')->setEditable('app_crudit_order_editdata', 'ROLE_ORDER_LABEL_EIP')],
            ['label' => 'new label'],
            ['ROLE_ORDER_LABEL_EIP']
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testFieldRoleIsChecked(): void
    {
        $this->datasource->expects(self::never())->method('fillFromData');

        $response = $this->editdata(
            [Field::new('label')->setEditInPlace(true)->setRole('ROLE_ADMIN')],
            ['label' => 'new label']
        );

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testEditableIfIsChecked(): void
    {
        $this->item->locked = true;
        $this->datasource->expects(self::never())->method('fillFromData');

        $response = $this->editdata(
            [Field::new('label')->setEditInPlace(true)->setEditableIf(fn(\stdClass $item) => !$item->locked)],
            ['label' => 'new label']
        );

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testUndeclaredFieldIsSavedWhenNotStrict(): void
    {
        $this->datasource->expects(self::once())->method('fillFromData');
        $this->logger->expects(self::once())->method('warning');

        $response = $this->editdata([], ['roles' => ['ROLE_ADMIN']], [], false);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testInvalidDataIsRefused(): void
    {
        $this->datasource->expects(self::never())->method('fillFromData');

        $response = $this->editdata([Field::new('label')->setEditInPlace(true)], 'label');

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    /**
     * @param Field[] $eipFields
     * @param string[] $roles granted in addition to ROLE_ORDER_EDIT
     */
    private function editdata(array $eipFields, mixed $data, array $roles = [], bool $strict = true): Response
    {
        $config = $this->createMock(CrudConfigInterface::class);
        $config->method('getName')->willReturn('ORDER');
        $config->method('getDatasource')->willReturn($this->datasource);
        $config->method('getEipFields')->willReturn($eipFields);

        $roles[] = 'ROLE_ORDER_EDIT';
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->method('isGranted')->willReturnCallback(
            function (mixed $attribute, mixed $subject = null) use ($roles): bool {
                $this->grantedChecks[] = [$attribute, $subject];

                return in_array($attribute, $roles, true);
            }
        );

        $container = new Container();
        $container->set('security.authorization_checker', $authorizationChecker);
        $container->set('parameter_bag', new ParameterBag(['lle_crudit.eip_strict' => $strict]));
        $container->set('logger', $this->logger);

        $controller = new class ($config) extends AbstractCrudController {
            use TraitCrudController;

            public function __construct(CrudConfigInterface $config)
            {
                $this->config = $config;
            }
        };
        $controller->setContainer($container);

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());

        $request = new Request(
            [],
            ['data' => json_encode($data)],
            ['id' => '1', '_route' => 'app_crudit_order_editdata']
        );

        return $controller->editdata('1', $request, $this->createMock(TranslatorInterface::class), $validator);
    }
}
