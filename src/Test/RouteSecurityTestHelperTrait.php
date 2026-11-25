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
 *  - ANONYMOUS_EXCLUDED_ROUTES : skipped by the anonymous test only (public in access_control, guarded in code);
 *  - ANONYMOUS_LOGIN_PATTERN : regex a redirect Location must match to count as a login redirect
 *    (default login|connect|oauth|sso|auth).
 *
 * The class using it must extend WebTestCase.
 */
trait RouteSecurityTestHelperTrait
{
    private const string DEFAULT_LOGIN_PATTERN = '#/(login|connect|oauth|sso|auth)#i';
    private const array DENIED_STATUS_CODES = [401, 403];

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
        $excludedRoutes = array_merge(
            $this->readRouteSecurityConstant('EXCLUDED_ROUTES'),
            $this->readRouteSecurityConstant('PUBLIC_ROUTES'),
            $this->readRouteSecurityConstant('ANONYMOUS_EXCLUDED_ROUTES'),
        );
        $excludedControllers = $this->readRouteSecurityConstant('EXCLUDED_CONTROLLERS');

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->disableReboot();

        $reached = [];
        foreach ($analyzer->inspect() as $entry) {
            if ($analyzer->isExcused($entry, $excludedRoutes, $excludedControllers, [], [])) {
                continue;
            }

            foreach ($entry['httpMethods'] as $httpMethod) {
                $client->request($httpMethod, $entry['samplePath']);
                $response = $client->getResponse();

                if (!$this->isAnonymousDenial($response->getStatusCode(), $response->headers->get('Location'))) {
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

        self::assertSame(
            [],
            $reached,
            sprintf("%d anonymous request(s) neither denied nor redirected to the login page:\n", count($reached))
            . implode("\n", $reached),
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
        );
    }

    private function isAnonymousDenial(int $statusCode, ?string $location): bool
    {
        if (in_array($statusCode, self::DENIED_STATUS_CODES, true)) {
            return true;
        }

        $constants = $this->readRouteSecurityConstant('ANONYMOUS_LOGIN_PATTERN');
        $pattern = $constants[0] ?? self::DEFAULT_LOGIN_PATTERN;

        return $statusCode >= 300 && $statusCode < 400 && $location !== null && preg_match($pattern, $location) === 1;
    }

    /**
     * @return string[]
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
