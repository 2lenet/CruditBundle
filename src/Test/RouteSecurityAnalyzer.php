<?php

namespace Lle\CruditBundle\Test;

use Closure;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Security\Http\AccessMapInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class RouteSecurityAnalyzer
{
    public const string REASON_PUBLIC = 'public (PUBLIC_ACCESS or no access_control rule)';
    public const string REASON_NO_FIREWALL = 'outside any firewall (security: false or no firewall covers it)';
    public const string REASON_ROLE_USER = 'ROLE_USER only, no right check';

    private const array PUBLIC_REASONS = [self::REASON_PUBLIC, self::REASON_NO_FIREWALL];
    private const array PUBLIC_ATTRIBUTES = ['PUBLIC_ACCESS', 'IS_AUTHENTICATED_ANONYMOUSLY'];
    private const array ALL_HTTP_METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
    private const string ROLE_USER = 'ROLE_USER';
    private const string AUTHENTICATED_PREFIX = 'IS_AUTHENTICATED';
    private const string DENY_CALL_PATTERN = '/\b(?:denyAccessUnlessGranted|denyUnlessAccessGranted)\(/';
    private const string IS_GRANTED_CALL_PATTERN = '/\bisGranted\(/';
    private const string DENIAL_PATTERN = '/\b(?:throw|createAccessDeniedException|AccessDenied\w*'
        . '|HTTP_FORBIDDEN|403)\b/';
    private const string TRIVIAL_CHECK_PATTERN = '/\b(?:denyAccessUnlessGranted|denyUnlessAccessGranted|isGranted)\('
        . '\s*[\'"](?:ROLE_USER|PUBLIC_ACCESS|IS_AUTHENTICATED\w*)[\'"]/';
    private const string HELPER_CALL_PATTERN = '/\$this->(\w+)\(/';
    private const string PLACEHOLDER_PATTERN = '/\{!?(\w+)\}/';
    private const string ALTERNATIVES_PATTERN = '/^[\w-]+(?:\|[\w-]+)*$/';
    private const string DEFAULT_PLACEHOLDER_VALUE = '1';
    private const string INTERNAL_ROUTE_PREFIX = '_';
    private const string FRAMEWORK_NAMESPACE = 'Symfony\\';
    private const string INVOKE = '__invoke';

    /**
     * @param Closure(Request): bool|null $hasFirewall tells whether a firewall covers the request
     */
    public function __construct(
        protected RouteCollection $routes,
        protected AccessMapInterface $accessMap,
        protected ?Closure $hasFirewall = null,
    ) {
    }

    /**
     * @return list<array{
     *     route: string,
     *     path: string,
     *     samplePath: string,
     *     httpMethods: list<string>,
     *     controller: string,
     *     class: class-string,
     *     reason: string|null,
     *     httpMethod: string|null
     * }>
     */
    public function inspect(): array
    {
        $entries = [];

        foreach ($this->routes as $name => $route) {
            $target = $this->resolveController($route);
            if ($target === null || str_starts_with($name, self::INTERNAL_ROUTE_PREFIX)) {
                continue;
            }

            [$class, $method] = $target;
            $httpMethods = $this->listHttpMethods($route);
            [$reason, $httpMethod] = $this->findViolation($route, $httpMethods, new ReflectionMethod($class, $method));

            $entries[] = [
                'route' => $name,
                'path' => $route->getPath(),
                'samplePath' => $this->buildSamplePath($route),
                'httpMethods' => $httpMethods,
                'controller' => $class . ($method === self::INVOKE ? '' : '::' . $method),
                'class' => $class,
                'reason' => $reason,
                'httpMethod' => $httpMethod,
            ];
        }

        return $entries;
    }

    /**
     * @param string[] $excludedRoutes checked elsewhere (service, listener...): glob on the route name
     * @param string[] $excludedControllers glob on the fully qualified controller class name
     * @param string[] $publicRoutes routes public on purpose: only excuse a public reason
     * @param string[] $roleUserRoutes routes open to any logged in user: only excuse the ROLE_USER reason
     * @return list<array{route: string, path: string, controller: string, reason: string, httpMethod: string}>
     */
    public function analyze(
        array $excludedRoutes = [],
        array $excludedControllers = [],
        array $publicRoutes = [],
        array $roleUserRoutes = [],
    ): array {
        $violations = [];

        foreach ($this->inspect() as $entry) {
            if ($entry['reason'] === null || $entry['httpMethod'] === null) {
                continue;
            }
            if ($this->isExcused($entry, $excludedRoutes, $excludedControllers, $publicRoutes, $roleUserRoutes)) {
                continue;
            }

            $violations[] = [
                'route' => $entry['route'],
                'path' => $entry['path'],
                'controller' => $entry['controller'],
                'reason' => $entry['reason'],
                'httpMethod' => $entry['httpMethod'],
            ];
        }

        return $violations;
    }

    /**
     * @param string[] $excludedRoutes
     * @param string[] $excludedControllers
     * @param string[] $publicRoutes
     * @param string[] $roleUserRoutes
     * @return string[]
     */
    public function findUnusedExclusions(
        array $excludedRoutes,
        array $excludedControllers,
        array $publicRoutes = [],
        array $roleUserRoutes = [],
    ): array {
        $entries = $this->inspect();
        $publicEntries = array_values(array_filter(
            $entries,
            fn (array $entry) => in_array($entry['reason'], self::PUBLIC_REASONS, true),
        ));
        $roleUserEntries = array_values(array_filter(
            $entries,
            fn (array $entry) => $entry['reason'] === self::REASON_ROLE_USER,
        ));

        return array_values(array_merge(
            $this->filterUnused($excludedRoutes, $entries, 'route'),
            $this->filterUnused($excludedControllers, $entries, 'class'),
            $this->filterUnused($publicRoutes, $publicEntries, 'route'),
            $this->filterUnused($roleUserRoutes, $roleUserEntries, 'route'),
        ));
    }

    /**
     * @param array{route: string, class: class-string, reason: string|null} $entry
     * @param string[] $excludedRoutes
     * @param string[] $excludedControllers
     * @param string[] $publicRoutes
     * @param string[] $roleUserRoutes
     */
    public function isExcused(
        array $entry,
        array $excludedRoutes,
        array $excludedControllers,
        array $publicRoutes,
        array $roleUserRoutes,
    ): bool {
        return $this->matchesAny($entry['route'], $excludedRoutes)
            || $this->matchesAny($entry['class'], $excludedControllers)
            || (
                in_array($entry['reason'], self::PUBLIC_REASONS, true)
                && $this->matchesAny($entry['route'], $publicRoutes)
            )
            || ($entry['reason'] === self::REASON_ROLE_USER && $this->matchesAny($entry['route'], $roleUserRoutes));
    }

    /**
     * @param string[] $patterns
     * @param list<array<string, mixed>> $entries
     * @return string[]
     */
    private function filterUnused(array $patterns, array $entries, string $key): array
    {
        $subjects = array_column($entries, $key);

        return array_filter($patterns, fn (string $pattern) => !$this->matchesAny($pattern, $subjects, true));
    }

    /**
     * @param string[] $patterns
     */
    private function matchesAny(string $subject, array $patterns, bool $subjectIsPattern = false): bool
    {
        foreach ($patterns as $pattern) {
            [$glob, $candidate] = $subjectIsPattern ? [$subject, $pattern] : [$pattern, $subject];
            if (fnmatch($glob, $candidate, FNM_NOESCAPE)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{0: class-string, 1: string}|null
     */
    private function resolveController(Route $route): ?array
    {
        $controller = $route->getDefault('_controller');
        if (!is_string($controller)) {
            return null;
        }

        [$class, $method] = str_contains($controller, '::') ? explode('::', $controller) : [$controller, self::INVOKE];
        if (!class_exists($class) || !method_exists($class, $method)) {
            return null;
        }

        return [$class, $method];
    }

    /**
     * @return list<string>
     */
    private function listHttpMethods(Route $route): array
    {
        return $route->getMethods() === [] ? self::ALL_HTTP_METHODS : array_values($route->getMethods());
    }

    private function buildSamplePath(Route $route): string
    {
        return preg_replace_callback(
            self::PLACEHOLDER_PATTERN,
            fn (array $matches) => $this->buildPlaceholderValue($route, $matches[1]),
            $route->getPath(),
        ) ?? $route->getPath();
    }

    private function buildPlaceholderValue(Route $route, string $name): string
    {
        $default = $route->getDefault($name);
        if (is_scalar($default) && $default !== '') {
            return (string) $default;
        }

        $requirement = $route->getRequirement($name);
        if (is_string($requirement) && preg_match(self::ALTERNATIVES_PATTERN, $requirement) === 1) {
            return explode('|', $requirement)[0];
        }

        return self::DEFAULT_PLACEHOLDER_VALUE;
    }

    /**
     * @param list<string> $httpMethods
     * @return array{0: string|null, 1: string|null} the reason and the HTTP method concerned
     */
    private function findViolation(Route $route, array $httpMethods, ReflectionMethod $method): array
    {
        if ($this->hasRealCheck($method)) {
            return [null, null];
        }

        foreach ($httpMethods as $httpMethod) {
            $reason = $this->findReasonForHttpMethod($route, $httpMethod);
            if ($reason !== null) {
                return [$reason, $httpMethod];
            }
        }

        return [null, null];
    }

    private function findReasonForHttpMethod(Route $route, string $httpMethod): ?string
    {
        $request = Request::create($this->buildSamplePath($route), $httpMethod);

        if ($this->hasFirewall !== null && !($this->hasFirewall)($request)) {
            return self::REASON_NO_FIREWALL;
        }

        $attributes = $this->accessMap->getPatterns($request)[0];
        if ($attributes === null || $attributes === []) {
            return self::REASON_PUBLIC;
        }

        if (array_filter($attributes, fn (mixed $attribute) => !$this->isTrivialAttribute($attribute)) !== []) {
            return null;
        }

        return array_diff($attributes, self::PUBLIC_ATTRIBUTES) === [] ? self::REASON_PUBLIC : self::REASON_ROLE_USER;
    }

    private function isTrivialAttribute(mixed $attribute): bool
    {
        return is_string($attribute)
            && (
                $attribute === self::ROLE_USER
                || in_array($attribute, self::PUBLIC_ATTRIBUTES, true)
                || str_starts_with($attribute, self::AUTHENTICATED_PREFIX)
            );
    }

    private function hasRealCheck(ReflectionMethod $method): bool
    {
        return $this->hasRealIsGranted($method->getAttributes(IsGranted::class))
            || $this->hasRealClassIsGranted($method->getDeclaringClass())
            || $this->hasRealCheckInCode($method, []);
    }

    private function hasRealClassIsGranted(ReflectionClass $class): bool
    {
        for ($current = $class; $current !== false; $current = $current->getParentClass()) {
            if ($this->hasRealIsGranted($current->getAttributes(IsGranted::class))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param ReflectionAttribute<IsGranted>[] $attributes
     */
    private function hasRealIsGranted(array $attributes): bool
    {
        foreach ($attributes as $attribute) {
            $arguments = $attribute->getArguments();
            $role = $arguments[0] ?? $arguments['attribute'] ?? null;
            if (!$this->isTrivialAttribute($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string[] $visited
     */
    private function hasRealCheckInCode(ReflectionMethod $method, array $visited): bool
    {
        $key = $method->getDeclaringClass()->getName() . '::' . $method->getName();
        $file = $method->getFileName();
        if (in_array($key, $visited, true) || $file === false) {
            return false;
        }
        $visited[] = $key;

        $length = $method->getEndLine() - $method->getStartLine() + 1;
        $lines = array_slice(file($file) ?: [], $method->getStartLine() - 1, $length);
        $source = $this->stripComments(implode('', $lines));
        $withoutTrivial = preg_replace(self::TRIVIAL_CHECK_PATTERN, '', $source) ?? $source;

        if ($this->containsGate($withoutTrivial)) {
            return true;
        }

        preg_match_all(self::HELPER_CALL_PATTERN, $source, $helperCalls);
        foreach (array_unique($helperCalls[1]) as $helperName) {
            if ($this->helperChecks($method->getDeclaringClass(), $helperName, $visited)) {
                return true;
            }
        }

        return false;
    }

    private function containsGate(string $source): bool
    {
        if (preg_match(self::DENY_CALL_PATTERN, $source) === 1) {
            return true;
        }

        return preg_match(self::IS_GRANTED_CALL_PATTERN, $source) === 1
            && preg_match(self::DENIAL_PATTERN, $source) === 1;
    }

    private function stripComments(string $source): string
    {
        $code = '';
        foreach (token_get_all('<?php ' . $source) as $token) {
            if (!is_array($token)) {
                $code .= $token;
            } elseif (!in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                $code .= $token[1];
            }
        }

        return $code;
    }

    /**
     * @param string[] $visited
     */
    private function helperChecks(ReflectionClass $class, string $helperName, array $visited): bool
    {
        if (!$class->hasMethod($helperName)) {
            return false;
        }

        $helper = $class->getMethod($helperName);

        return !str_starts_with($helper->getDeclaringClass()->getName(), self::FRAMEWORK_NAMESPACE)
            && $this->hasRealCheckInCode($helper, $visited);
    }
}
