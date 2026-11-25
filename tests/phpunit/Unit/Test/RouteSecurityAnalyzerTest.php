<?php

namespace phpunit\Unit\Test;

use Lle\CruditBundle\Test\RouteSecurityAnalyzer;
use phpunit\Unit\Test\Fixtures\ChildOfGuardedController;
use phpunit\Unit\Test\Fixtures\GuardedChildController;
use phpunit\Unit\Test\Fixtures\GuardedClassController;
use phpunit\Unit\Test\Fixtures\OpenInvokableController;
use phpunit\Unit\Test\Fixtures\SampleController;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher\MethodRequestMatcher;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Security\Http\AccessMap;
use Symfony\Component\Security\Http\AccessMapInterface;

class RouteSecurityAnalyzerTest extends TestCase
{
    private function buildAccessMap(): AccessMapInterface
    {
        return new class implements AccessMapInterface {
            public function expression(string $expression): \Stringable
            {
                return new class ($expression) implements \Stringable {
                    public function __construct(private readonly string $expression)
                    {
                    }

                    public function __toString(): string
                    {
                        return $this->expression;
                    }
                };
            }

            public function getPatterns(Request $request): array
            {
                $path = $request->getPathInfo();

                return match (true) {
                    str_starts_with($path, '/public') => [['PUBLIC_ACCESS'], null],
                    str_starts_with($path, '/admin') => [['ROLE_ADMIN'], null],
                    str_starts_with($path, '/fr/') => [['ROLE_ADMIN'], null],
                    str_starts_with($path, '/images') && $request->getMethod() === 'GET' => [['PUBLIC_ACCESS'], null],
                    str_starts_with($path, '/images') => [['ROLE_ADMIN'], null],
                    str_starts_with($path, '/postopen') && $request->isMethod('POST') => [['PUBLIC_ACCESS'], null],
                    str_starts_with($path, '/postopen') => [['ROLE_ADMIN'], null],
                    str_starts_with($path, '/expr-trivial') => [[$this->expression("is_granted('ROLE_USER')")], null],
                    str_starts_with($path, '/expr-admin') => [[$this->expression("is_granted('ROLE_ADMIN')")], null],
                    str_starts_with($path, '/nofirewall') => [null, null],
                    default => [['ROLE_USER'], null],
                };
            }
        };
    }

    private function buildAnalyzer(RouteCollection $routes): RouteSecurityAnalyzer
    {
        return new RouteSecurityAnalyzer($routes, $this->buildAccessMap());
    }

    private function routeTo(string $path, string $controller): Route
    {
        return new Route($path, ['_controller' => $controller]);
    }

    /**
     * @param string[] $methods
     * @param array<string, string> $requirements
     * @param array<string, string> $defaults
     */
    private function routeOn(
        string $path,
        string $controller,
        array $methods = [],
        array $requirements = [],
        array $defaults = [],
    ): Route {
        return new Route($path, ['_controller' => $controller] + $defaults, $requirements, methods: $methods);
    }

    private function sample(string $method): string
    {
        return SampleController::class . '::' . $method;
    }

    /**
     * @return iterable<string, array{string, string|null}>
     */
    public static function controllerProvider(): iterable
    {
        $sample = SampleController::class . '::';

        yield 'IsGranted on the method' => [$sample . 'guardedByAttribute', null];
        yield 'denyAccessUnlessGranted' => [$sample . 'guardedByCall', null];
        yield 'check in a called method' => [$sample . 'guardedByHelper', null];
        yield 'isGranted' => [$sample . 'guardedByIsGranted', null];
        yield 'isGranted followed by an exception' => [$sample . 'guardedByThrow', null];
        yield 'isGranted for display only' => [
            $sample . 'displayOnlyIsGranted',
            RouteSecurityAnalyzer::REASON_ROLE_USER,
        ];
        yield 'commented out check' => [$sample . 'commentedOutCheck', RouteSecurityAnalyzer::REASON_ROLE_USER];
        yield 'IsGranted on the class' => [GuardedClassController::class, null];
        yield 'IsGranted ROLE_USER' => [$sample . 'trivialAttribute', RouteSecurityAnalyzer::REASON_ROLE_USER];
        yield 'denyAccessUnlessGranted ROLE_USER' => [$sample . 'trivialCall', RouteSecurityAnalyzer::REASON_ROLE_USER];
        yield 'IsGranted only on the parent class' => [
            ChildOfGuardedController::class . '::inherited',
            RouteSecurityAnalyzer::REASON_ROLE_USER,
        ];
        yield 'IsGranted on the concrete class, inherited method' => [
            GuardedChildController::class . '::inherited',
            null,
        ];
        yield 'isGranted that throws when granted' => [
            $sample . 'throwsWhenGranted',
            RouteSecurityAnalyzer::REASON_ROLE_USER,
        ];
        yield 'negated isGranted without denial' => [
            $sample . 'negatedWithoutDenial',
            RouteSecurityAnalyzer::REASON_ROLE_USER,
        ];
        yield 'check at the top of a try block' => [$sample . 'guardedInsideTry', null];
        yield 'conditional check inside a try block' => [
            $sample . 'conditionalCheckInsideTry',
            RouteSecurityAnalyzer::REASON_ROLE_USER,
        ];
        yield 'conditional denyAccessUnlessGranted' => [
            $sample . 'conditionalCheck',
            RouteSecurityAnalyzer::REASON_ROLE_USER,
        ];
        yield 'conditional call to a checking method' => [
            $sample . 'conditionalHelper',
            RouteSecurityAnalyzer::REASON_ROLE_USER,
        ];
        yield 'display isGranted and an unrelated throw' => [
            $sample . 'displayIsGrantedWithUnrelatedThrow',
            RouteSecurityAnalyzer::REASON_ROLE_USER,
        ];
        yield 'no check' => [$sample . 'unguarded', RouteSecurityAnalyzer::REASON_ROLE_USER];
        yield 'methods calling each other' => [
            $sample . 'unguardedWithLoopingHelper',
            RouteSecurityAnalyzer::REASON_ROLE_USER,
        ];
        yield 'open invokable controller' => [
            OpenInvokableController::class,
            RouteSecurityAnalyzer::REASON_ROLE_USER,
        ];
    }

    #[DataProvider('controllerProvider')]
    public function testControllerCheckDetection(string $controller, ?string $expectedReason): void
    {
        $routes = new RouteCollection();
        $routes->add('target', $this->routeTo('/commande/{id}', $controller));

        $violations = $this->buildAnalyzer($routes)->analyze();

        if ($expectedReason === null) {
            self::assertSame([], $violations);

            return;
        }

        self::assertCount(1, $violations);
        self::assertSame('target', $violations[0]['route']);
        self::assertSame('/commande/{id}', $violations[0]['path']);
        self::assertSame($controller, $violations[0]['controller']);
        self::assertSame($expectedReason, $violations[0]['reason']);
    }

    public function testPublicRouteWithoutCheckIsReported(): void
    {
        $routes = new RouteCollection();
        $routes->add('download', $this->routeTo('/public/file/{id}', $this->sample('unguarded')));

        $violations = $this->buildAnalyzer($routes)->analyze();

        self::assertCount(1, $violations);
        self::assertSame(RouteSecurityAnalyzer::REASON_PUBLIC, $violations[0]['reason']);
    }

    public function testPublicRouteWithRealCheckIsNotReported(): void
    {
        $routes = new RouteCollection();
        $routes->add('download', $this->routeTo('/public/file', $this->sample('guardedByCall')));

        self::assertSame([], $this->buildAnalyzer($routes)->analyze());
    }

    public function testAccessControlRoleProtectsTheRoute(): void
    {
        $routes = new RouteCollection();
        $routes->add('admin', $this->routeTo('/admin/page', $this->sample('unguarded')));

        self::assertSame([], $this->buildAnalyzer($routes)->analyze());
    }

    public function testInternalRoutesAndRoutesWithoutControllerAreIgnored(): void
    {
        $routes = new RouteCollection();
        $routes->add('_wdt', $this->routeTo('/_wdt/{token}', $this->sample('unguarded')));
        $routes->add('no_controller', new Route('/logout'));

        self::assertSame([], $this->buildAnalyzer($routes)->analyze());
    }

    public function testUnresolvedControllersAreReportedUnlessAccessControlProtectsThem(): void
    {
        $routes = new RouteCollection();
        $routes->add('closure', new Route('/closure', ['_controller' => static fn () => null]));
        $routes->add('service', $this->routeTo('/service', 'some.service:action'));
        $routes->add('missing_method', $this->routeTo('/missing', SampleController::class . '::nothing'));
        $routes->add('admin_service', $this->routeTo('/admin/service', 'some.service:action'));

        $violations = $this->buildAnalyzer($routes)->analyze();

        self::assertSame(['closure', 'service', 'missing_method'], array_column($violations, 'route'));
        self::assertSame(
            [RouteSecurityAnalyzer::REASON_ROLE_USER],
            array_unique(array_column($violations, 'reason')),
        );
        self::assertSame('some.service:action', $violations[1]['controller']);
    }

    public function testLegacyAndArrayControllerNotationsAreResolved(): void
    {
        $routes = new RouteCollection();
        $routes->add('legacy', $this->routeTo('/legacy', SampleController::class . ':guardedByAttribute'));
        $routes->add('array', new Route('/array', ['_controller' => [SampleController::class, 'unguarded']]));

        $violations = $this->buildAnalyzer($routes)->analyze();

        self::assertSame(['array'], array_column($violations, 'route'));
        self::assertSame($this->sample('unguarded'), $violations[0]['controller']);
    }

    public function testAllowIfExpressionsAreOnlyTrustedWhenTheyDoMoreThanRoleUser(): void
    {
        $routes = new RouteCollection();
        $routes->add('trivial', $this->routeTo('/expr-trivial/a', $this->sample('unguarded')));
        $routes->add('real', $this->routeTo('/expr-admin/a', $this->sample('unguarded')));

        $violations = $this->buildAnalyzer($routes)->analyze();

        self::assertSame(['trivial'], array_column($violations, 'route'));
        self::assertSame(RouteSecurityAnalyzer::REASON_ROLE_USER, $violations[0]['reason']);
    }

    public function testExcludedRoutesByGlob(): void
    {
        $routes = new RouteCollection();
        $routes->add('login', $this->routeTo('/public/login', $this->sample('unguarded')));
        $routes->add('app_api_a', $this->routeTo('/api/a', $this->sample('unguarded')));
        $routes->add('other', $this->routeTo('/other', $this->sample('unguarded')));

        $violations = $this->buildAnalyzer($routes)->analyze(['login', 'app_api_*']);

        self::assertCount(1, $violations);
        self::assertSame('other', $violations[0]['route']);
    }

    public function testExcludedControllersByGlob(): void
    {
        $routes = new RouteCollection();
        $routes->add('sample', $this->routeTo('/sample', $this->sample('unguarded')));
        $routes->add('open', $this->routeTo('/open', OpenInvokableController::class));

        $violations = $this->buildAnalyzer($routes)->analyze([], [SampleController::class]);

        self::assertCount(1, $violations);
        self::assertSame('open', $violations[0]['route']);
    }

    public function testUnusedExclusionsAreListed(): void
    {
        $routes = new RouteCollection();
        $routes->add('sample', $this->routeTo('/sample', $this->sample('unguarded')));

        $analyzer = $this->buildAnalyzer($routes);

        self::assertSame(
            ['gone', 'Old\\Controller\\*'],
            $analyzer->findUnusedExclusions(['sample', 'gone'], ['Old\\Controller\\*', SampleController::class]),
        );
    }

    public function testRouteMethodIsUsedToMatchAccessControl(): void
    {
        $routes = new RouteCollection();
        $routes->add('save', new Route('/admin/save', ['_controller' => $this->sample('unguarded')], methods: ['PUT']));

        self::assertSame([], $this->buildAnalyzer($routes)->analyze());
    }

    public function testEveryHttpMethodOfTheRouteIsChecked(): void
    {
        $routes = new RouteCollection();
        $routes->add('both', $this->routeOn('/images/a', $this->sample('unguarded'), ['GET', 'POST']));
        $routes->add('post_only', $this->routeOn('/images/b', $this->sample('unguarded'), ['POST']));
        $routes->add('any', $this->routeTo('/images/c', $this->sample('unguarded')));

        $violations = $this->buildAnalyzer($routes)->analyze();

        self::assertSame(['both', 'any'], array_column($violations, 'route'));
        self::assertSame(['GET', 'GET'], array_column($violations, 'httpMethod'));
        self::assertSame(
            [RouteSecurityAnalyzer::REASON_PUBLIC, RouteSecurityAnalyzer::REASON_PUBLIC],
            array_column($violations, 'reason'),
        );
    }

    public function testRouteOpenOnASingleMethodIsReported(): void
    {
        $routes = new RouteCollection();
        $routes->add('declared', $this->routeOn('/postopen/a', $this->sample('unguarded'), ['GET', 'POST']));
        $routes->add('undeclared', $this->routeTo('/postopen/b', $this->sample('unguarded')));
        $routes->add('get_only', $this->routeOn('/postopen/c', $this->sample('unguarded'), ['GET']));

        $violations = $this->buildAnalyzer($routes)->analyze();

        self::assertSame(['declared', 'undeclared'], array_column($violations, 'route'));
        self::assertSame(['POST', 'POST'], array_column($violations, 'httpMethod'));
    }

    public function testSamplePathUsesRequirementsThenDefaults(): void
    {
        $routes = new RouteCollection();
        $unguarded = $this->sample('unguarded');
        $routes->add('required', $this->routeOn('/{_locale}/secure', $unguarded, [], ['_locale' => 'fr|en']));
        $routes->add('defaulted', $this->routeOn('/{_locale}/page', $unguarded, [], [], ['_locale' => 'fr']));
        $routes->add('plain', $this->routeTo('/{_locale}/other', $this->sample('unguarded')));

        $violations = $this->buildAnalyzer($routes)->analyze();

        self::assertSame(['plain'], array_column($violations, 'route'));
    }

    public function testRouteOutsideAnyFirewallIsReported(): void
    {
        $routes = new RouteCollection();
        $routes->add('open', $this->routeTo('/nofirewall/a', $this->sample('unguarded')));
        $routes->add('guarded', $this->routeTo('/nofirewall/b', $this->sample('guardedByAttribute')));
        $routes->add('covered', $this->routeTo('/admin/c', $this->sample('unguarded')));
        $analyzer = new RouteSecurityAnalyzer(
            $routes,
            $this->buildAccessMap(),
            static fn (Request $request): bool => !str_starts_with($request->getPathInfo(), '/nofirewall'),
        );

        $violations = $analyzer->analyze();

        self::assertCount(1, $violations);
        self::assertSame('open', $violations[0]['route']);
        self::assertSame(RouteSecurityAnalyzer::REASON_NO_FIREWALL, $violations[0]['reason']);
    }

    public function testPublicRoutesOnlyExcuseAPublicReason(): void
    {
        $routes = new RouteCollection();
        $routes->add('public_ok', $this->routeTo('/public/a', $this->sample('unguarded')));
        $routes->add('now_logged_only', $this->routeTo('/other', $this->sample('unguarded')));

        $violations = $this->buildAnalyzer($routes)->analyze([], [], ['public_ok', 'now_logged_only']);

        self::assertSame(['now_logged_only'], array_column($violations, 'route'));
    }

    public function testRoleUserRoutesOnlyExcuseARoleUserReason(): void
    {
        $routes = new RouteCollection();
        $routes->add('logged_ok', $this->routeTo('/other', $this->sample('unguarded')));
        $routes->add('now_public', $this->routeTo('/public/a', $this->sample('unguarded')));

        $violations = $this->buildAnalyzer($routes)->analyze([], [], [], ['logged_ok', 'now_public']);

        self::assertSame(['now_public'], array_column($violations, 'route'));
    }

    public function testStaleTypedExclusionsAreListed(): void
    {
        $routes = new RouteCollection();
        $routes->add('public_ok', $this->routeTo('/public/a', $this->sample('unguarded')));
        $routes->add('logged_ok', $this->routeTo('/other', $this->sample('unguarded')));
        $routes->add('fixed', $this->routeTo('/other2', $this->sample('guardedByAttribute')));

        $stale = $this->buildAnalyzer($routes)->findUnusedExclusions(
            [],
            [],
            ['public_ok', 'logged_ok', 'fixed'],
            ['logged_ok', 'public_ok', 'fixed'],
        );

        self::assertSame(['logged_ok', 'fixed', 'public_ok', 'fixed'], $stale);
    }

    public function testWithTheRealAccessMap(): void
    {
        $accessMap = new AccessMap();
        $accessMap->add(new MethodRequestMatcher(['GET']), ['PUBLIC_ACCESS']);
        $routes = new RouteCollection();
        $routes->add('read', new Route('/x', ['_controller' => $this->sample('unguarded')], methods: ['GET']));
        $routes->add('write', new Route('/x', ['_controller' => $this->sample('unguarded')], methods: ['POST']));

        $violations = (new RouteSecurityAnalyzer($routes, $accessMap))->analyze();

        self::assertSame(
            ['read' => RouteSecurityAnalyzer::REASON_PUBLIC, 'write' => RouteSecurityAnalyzer::REASON_PUBLIC],
            array_column($violations, 'reason', 'route'),
        );
    }

    public function testInspectListsEveryControllerRouteWithItsSamplePath(): void
    {
        $routes = new RouteCollection();
        $routes->add('a', new Route('/admin/{id}', ['_controller' => $this->sample('unguarded')], methods: ['PUT']));
        $routes->add('_internal', $this->routeTo('/_x', $this->sample('unguarded')));

        $entries = $this->buildAnalyzer($routes)->inspect();

        self::assertCount(1, $entries);
        self::assertSame('/admin/1', $entries[0]['samplePath']);
        self::assertSame(['PUT'], $entries[0]['httpMethods']);
        self::assertNull($entries[0]['reason']);
    }

    public function testExclusionOfARouteThatGotProtectedIsStale(): void
    {
        $routes = new RouteCollection();
        $routes->add('fixed', $this->routeTo('/other', $this->sample('guardedByAttribute')));
        $routes->add('still_open', $this->routeTo('/other2', $this->sample('unguarded')));

        $stale = $this->buildAnalyzer($routes)->findUnusedExclusions(
            ['fixed', 'still_open'],
            [SampleController::class],
        );

        self::assertSame(['fixed'], $stale);
    }

    public function testFindMatchingPatterns(): void
    {
        $analyzer = $this->buildAnalyzer(new RouteCollection());

        $patterns = ['app_*', 'x', 'app_api_a'];

        self::assertSame(['app_*', 'app_api_a'], $analyzer->findMatchingPatterns('app_api_a', $patterns));
        self::assertSame([], $analyzer->findMatchingPatterns('other', ['app_*']));
    }
}
