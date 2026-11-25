<?php

namespace Lle\CruditBundle\Test;

use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\AccessMapInterface;

/**
 * Constants read on the test class (all optional, fnmatch globs):
 *  - EXCLUDED_ROUTES / EXCLUDED_CONTROLLERS : right check done elsewhere (service, listener...);
 *  - PUBLIC_ROUTES : public on purpose, only excuse a route that really is public;
 *  - ROLE_USER_ROUTES : open to any logged in user, only excuse a route that really is ROLE_USER only;
 *  - ANONYMOUS_EXCLUDED_ROUTES : skipped by the anonymous test only (public in access_control, guarded in code,
 *    or that no sample path can reach);
 *  - ROUTE_SAMPLE_PARAMETERS : placeholder values by route name glob, for the anonymous test
 *    (['*' => ['uuid' => '0190...'], 'app_blog_*' => ['slug' => 'hello']]);
 *  - ANONYMOUS_LOGIN_PATTERN : regex a redirect Location must match to count as a login redirect
 *    (default: a path segment starting with login, connect, oauth, sso or auth).
 *
 * The class using it must extend WebTestCase.
 */
trait RouteSecurityTestHelperTrait
{
    private const array OUTCOMES_NOT_REACHING_THE_ROUTE = [
        RouteSecurityAnalyzer::OUTCOME_DENIED,
        RouteSecurityAnalyzer::OUTCOME_SHADOWED,
    ];

    public function testEveryRouteIsProtected(): void
    {
        $violations = $this->buildRouteSecurityAnalyzer()->analyze(
            $this->readRouteSecurityConstant('EXCLUDED_ROUTES'),
            $this->readRouteSecurityConstant('EXCLUDED_CONTROLLERS'),
            $this->readRouteSecurityConstant('PUBLIC_ROUTES'),
            $this->readRouteSecurityConstant('ROLE_USER_ROUTES'),
        );

        self::assertSame([], $violations, $this->formatRouteSecurityViolations($violations));
    }

    public function testExclusionsAreStillUsed(): void
    {
        $unused = $this->buildRouteSecurityAnalyzer()->findUnusedExclusions(
            $this->readRouteSecurityConstant('EXCLUDED_ROUTES'),
            $this->readRouteSecurityConstant('EXCLUDED_CONTROLLERS'),
            $this->readRouteSecurityConstant('PUBLIC_ROUTES'),
            $this->readRouteSecurityConstant('ROLE_USER_ROUTES'),
        );

        self::assertSame(
            [],
            $unused,
            "Exclusions that no longer excuse any route:\n- " . implode("\n- ", $unused),
        );
    }

    public function testAnonymousIsDeniedEverywhere(): void
    {
        $analyzer = $this->buildRouteSecurityAnalyzer();
        $entries = $analyzer->inspect();
        $excludedRoutes = array_merge(
            $this->readRouteSecurityConstant('EXCLUDED_ROUTES'),
            $this->readRouteSecurityConstant('PUBLIC_ROUTES'),
        );
        $excludedControllers = $this->readRouteSecurityConstant('EXCLUDED_CONTROLLERS');
        $anonymousExcluded = $this->readRouteSecurityConstant('ANONYMOUS_EXCLUDED_ROUTES');

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->disableReboot();

        $reached = [];
        $untestable = [];
        $usedAnonymousExclusions = [];
        foreach ($entries as $entry) {
            if ($analyzer->isExcused($entry, $excludedRoutes, $excludedControllers, [], [])) {
                continue;
            }
            $anonymousPatterns = $analyzer->findMatchingPatterns($entry['route'], $anonymousExcluded);

            foreach ($entry['httpMethods'] as $httpMethod) {
                $client->request($httpMethod, $entry['samplePath']);
                $response = $client->getResponse();
                $outcome = $analyzer->classifyAnonymousResponse(
                    $entry,
                    $client->getRequest()->attributes->get('_route'),
                    $response->getStatusCode(),
                    $response->headers->get('Location'),
                    $this->readRouteSecurityConstant('ANONYMOUS_LOGIN_PATTERN')[0] ?? null,
                );

                if (in_array($outcome, self::OUTCOMES_NOT_REACHING_THE_ROUTE, true)) {
                    continue;
                }

                $usedAnonymousExclusions = array_merge($usedAnonymousExclusions, $anonymousPatterns);
                if ($outcome === RouteSecurityAnalyzer::OUTCOME_UNTESTABLE) {
                    if ($anonymousPatterns === []) {
                        $untestable[$entry['route']] = $entry['samplePath'];
                    }

                    continue 2;
                }

                if ($anonymousPatterns === []) {
                    $reached[] = sprintf(
                        '- %s  %s %s  [%s]  anonymous → %d',
                        $entry['route'],
                        $httpMethod,
                        $entry['samplePath'],
                        $entry['controller'],
                        $response->getStatusCode(),
                    );
                }
            }
        }

        $untestableReport = $this->formatUntestableRoutes($untestable);
        self::assertSame(
            [],
            $reached,
            sprintf("%d anonymous request(s) neither denied nor redirected to the login page:\n", count($reached))
            . implode("\n", $reached) . $untestableReport,
        );

        $unusedAnonymousExclusions = array_values(array_diff($anonymousExcluded, $usedAnonymousExclusions));
        self::assertSame(
            [],
            $unusedAnonymousExclusions,
            "ANONYMOUS_EXCLUDED_ROUTES entries that no anonymous request reaches any more:\n- "
            . implode("\n- ", $unusedAnonymousExclusions) . $untestableReport,
        );

        self::assertSame([], $untestable, ltrim($untestableReport));
    }

    /**
     * @param array<string, string> $untestable sample path by route name
     */
    private function formatUntestableRoutes(array $untestable): string
    {
        if ($untestable === []) {
            return '';
        }

        $lines = [];
        foreach ($untestable as $route => $path) {
            $lines[] = '- ' . $route . '  ' . $path;
        }

        return sprintf(
            "\n\n%d route(s) could not be tested anonymously (the sample path does not resolve to the route)."
            . " Give the placeholders a value in ROUTE_SAMPLE_PARAMETERS, or list the route in"
            . " ANONYMOUS_EXCLUDED_ROUTES:\n%s",
            count($untestable),
            implode("\n", $lines),
        );
    }

    protected function buildRouteSecurityAnalyzer(): RouteSecurityAnalyzer
    {
        self::bootKernel();
        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        /** @var AccessMapInterface $accessMap */
        $accessMap = static::getContainer()->get('security.access_map');
        /** @var FirewallMap $firewallMap */
        $firewallMap = static::getContainer()->get('security.firewall.map');

        return new RouteSecurityAnalyzer(
            $router->getRouteCollection(),
            $accessMap,
            static fn (Request $request): bool => $firewallMap->getFirewallConfig($request) !== null,
            $this->readRouteSecurityConstant('ROUTE_SAMPLE_PARAMETERS'),
        );
    }

    /**
     * @return array<mixed>
     */
    private function readRouteSecurityConstant(string $name): array
    {
        $constant = static::class . '::' . $name;
        if (!defined($constant)) {
            return [];
        }

        return (array) constant($constant);
    }

    /**
     * @param list<array<string, string>> $violations
     */
    private function formatRouteSecurityViolations(array $violations): string
    {
        $lines = [sprintf('%d route(s) without a right check:', count($violations))];
        foreach ($violations as $violation) {
            $lines[] = sprintf(
                '- %s  %s %s  [%s]  %s',
                $violation['route'],
                $violation['httpMethod'],
                $violation['path'],
                $violation['controller'],
                $violation['reason'],
            );
        }

        return implode("\n", $lines);
    }
}
