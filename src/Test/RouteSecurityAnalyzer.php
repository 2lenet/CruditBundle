<?php

namespace Lle\CruditBundle\Test;

use Closure;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use Stringable;
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

    public const string OUTCOME_DENIED = 'denied';
    public const string OUTCOME_REACHED = 'reached';
    public const string OUTCOME_SHADOWED = 'shadowed';
    public const string OUTCOME_UNTESTABLE = 'untestable';

    private const array DENIED_STATUS_CODES = [401, 403];
    private const string DEFAULT_LOGIN_PATTERN = '#/(?:login|connect|oauth|sso|auth)(?:[/?_-]|$)#i';
    private const array PUBLIC_REASONS = [self::REASON_PUBLIC, self::REASON_NO_FIREWALL];
    private const array PUBLIC_ATTRIBUTES = ['PUBLIC_ACCESS', 'IS_AUTHENTICATED_ANONYMOUSLY'];
    private const array ALL_HTTP_METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
    private const array DENY_CALLS = ['denyAccessUnlessGranted', 'denyUnlessAccessGranted'];
    private const array STATEMENT_END_TOKENS = [';', '{', '}'];
    private const array TRANSPARENT_TOKENS = [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT];
    private const array CHAIN_TOKENS = [
        T_WHITESPACE,
        T_OBJECT_OPERATOR,
        T_NULLSAFE_OBJECT_OPERATOR,
        T_VARIABLE,
        T_STRING,
    ];
    private const array OPENING_BRACE_TOKENS = [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES];
    private const string IS_GRANTED_CALL = 'isGranted';
    private const string THIS_VARIABLE = '$this';
    private const string ROLE_USER = 'ROLE_USER';
    private const string AUTHENTICATED_PREFIX = 'IS_AUTHENTICATED';
    private const string TRIVIAL_ROLE = 'ROLE_USER|PUBLIC_ACCESS|IS_AUTHENTICATED\w*';
    private const string TRIVIAL_CALL_PATTERN = '/^\w+\(\s*(?:[\'"](?:' . self::TRIVIAL_ROLE . ')[\'"]'
        . '|[\w\\\\]+::(?:' . self::TRIVIAL_ROLE . ')\b)/';
    private const string EXPRESSION_ROLE_PATTERN = '/^\s*is_granted\(\s*[\'"]([^\'"]+)[\'"]\s*\)\s*$/';
    private const string EXPRESSION_AUTHENTICATED_PATTERN = '/^\s*'
        . '(?:is_authenticated|is_fully_authenticated|is_remember_me)\(\)\s*$/';
    private const string AUTHENTICATED_LABEL = 'IS_AUTHENTICATED';
    private const string NEGATED_GUARD_PATTERN = '/^isGranted\((?:[^()]|\((?:[^()]|\([^()]*\))*\))*\)\s*\)\s*\{?'
        . '[^;}]*?(?:\bthrow\b|createAccessDeniedException|AccessDenied\w*|HTTP_FORBIDDEN|\b403\b)/s';
    private const string PLACEHOLDER_PATTERN = '/\{!?(\w+)\}/';
    private const string ALTERNATIVES_PATTERN = '/^[\w-]+(?:\|[\w-]+)*$/';
    private const string LEGACY_CONTROLLER_PATTERN = '/^([^:]+):(\w+)$/';
    private const string DEFAULT_PLACEHOLDER_VALUE = '1';
    private const string INTERNAL_ROUTE_PREFIX = '_';
    private const string FRAMEWORK_NAMESPACE = 'Symfony\\';
    private const string INVOKE = '__invoke';
    private const int METHOD_BODY_DEPTH = 1;

    /**
     * @param Closure(Request): bool|null $hasFirewall tells whether a firewall covers the request
     * @param array<string, array<string, string>> $sampleParameters placeholder values by route name glob
     */
    public function __construct(
        protected RouteCollection $routes,
        protected AccessMapInterface $accessMap,
        protected ?Closure $hasFirewall = null,
        protected array $sampleParameters = [],
    ) {
    }

    /**
     * @return list<array{
     *     route: string,
     *     path: string,
     *     samplePath: string,
     *     httpMethods: list<string>,
     *     controller: string,
     *     class: class-string|null,
     *     reason: string|null,
     *     httpMethod: string|null
     * }>
     */
    public function inspect(): array
    {
        $entries = [];

        foreach ($this->routes as $name => $route) {
            $controller = $route->getDefault('_controller');
            if ($controller === null || str_starts_with($name, self::INTERNAL_ROUTE_PREFIX)) {
                continue;
            }

            $target = $this->resolveController($route);
            $httpMethods = $this->listHttpMethods($route);
            [$reason, $httpMethod] = $this->findViolation($name, $route, $httpMethods, $target);

            $entries[] = [
                'route' => $name,
                'path' => $route->getPath(),
                'samplePath' => $this->buildSamplePath($name, $route),
                'httpMethods' => $httpMethods,
                'controller' => $target === null
                    ? $this->describeUnresolvedController($controller)
                    : $target[0] . ($target[1] === self::INVOKE ? '' : '::' . $target[1]),
                'class' => $target[0] ?? null,
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
     * An exclusion is unused when it excuses nothing: no route is left to excuse
     * (a route that got protected no longer needs it).
     *
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
        $exposed = array_values(array_filter($this->inspect(), fn (array $entry) => $entry['reason'] !== null));
        $publicEntries = array_values(array_filter(
            $exposed,
            fn (array $entry) => in_array($entry['reason'], self::PUBLIC_REASONS, true),
        ));
        $roleUserEntries = array_values(array_filter(
            $exposed,
            fn (array $entry) => $entry['reason'] === self::REASON_ROLE_USER,
        ));

        return array_values(array_merge(
            $this->filterUnused($excludedRoutes, $exposed, 'route'),
            $this->filterUnused($excludedControllers, $exposed, 'class'),
            $this->filterUnused($publicRoutes, $publicEntries, 'route'),
            $this->filterUnused($roleUserRoutes, $roleUserEntries, 'route'),
        ));
    }

    /**
     * @param array{route: string, class: class-string|null, reason: string|null} $entry
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
        return $this->findMatchingPatterns($entry['route'], $excludedRoutes) !== []
            || ($entry['class'] !== null && $this->findMatchingPatterns($entry['class'], $excludedControllers) !== [])
            || (
                in_array($entry['reason'], self::PUBLIC_REASONS, true)
                && $this->findMatchingPatterns($entry['route'], $publicRoutes) !== []
            )
            || (
                $entry['reason'] === self::REASON_ROLE_USER
                && $this->findMatchingPatterns($entry['route'], $roleUserRoutes) !== []
            );
    }

    /**
     * Tells what an anonymous request to the sample path of an entry says about that entry.
     * Routing runs before the firewall, so the route that answered must be the one under test; a route hidden
     * by another route with the same path is never served, and is no reason to skip its other HTTP methods.
     *
     * @param array{route: string, path: string} $entry
     * @return self::OUTCOME_*
     */
    public function classifyAnonymousResponse(
        array $entry,
        mixed $matchedRoute,
        int $statusCode,
        ?string $location,
        ?string $loginPattern = null,
    ): string {
        if ($matchedRoute !== $entry['route']) {
            $matched = is_string($matchedRoute) ? $this->routes->get($matchedRoute) : null;

            return $matched?->getPath() === $entry['path'] ? self::OUTCOME_SHADOWED : self::OUTCOME_UNTESTABLE;
        }

        $isLoginRedirect = $statusCode >= 300 && $statusCode < 400 && $location !== null
            && preg_match($loginPattern ?? self::DEFAULT_LOGIN_PATTERN, $location) === 1;

        return in_array($statusCode, self::DENIED_STATUS_CODES, true) || $isLoginRedirect
            ? self::OUTCOME_DENIED
            : self::OUTCOME_REACHED;
    }

    /**
     * @param string[] $patterns fnmatch globs
     * @return string[] the patterns matching the subject
     */
    public function findMatchingPatterns(string $subject, array $patterns): array
    {
        return array_values(array_filter(
            $patterns,
            fn (string $pattern) => fnmatch($pattern, $subject, FNM_NOESCAPE),
        ));
    }

    /**
     * @param string[] $patterns
     * @param list<array<string, mixed>> $entries
     * @return string[]
     */
    private function filterUnused(array $patterns, array $entries, string $key): array
    {
        $subjects = array_filter(array_column($entries, $key), 'is_string');

        return array_filter(
            $patterns,
            function (string $pattern) use ($subjects) {
                foreach ($subjects as $subject) {
                    if (fnmatch($pattern, $subject, FNM_NOESCAPE)) {
                        return false;
                    }
                }

                return true;
            },
        );
    }

    /**
     * @return array{0: class-string, 1: string}|null
     */
    private function resolveController(Route $route): ?array
    {
        $controller = $route->getDefault('_controller');

        if ($this->isCallableArray($controller)) {
            [$class, $method] = $controller;
        } elseif (is_string($controller) && str_contains($controller, '::')) {
            [$class, $method] = explode('::', $controller, 2);
        } elseif (is_string($controller) && preg_match(self::LEGACY_CONTROLLER_PATTERN, $controller, $matches) === 1) {
            [, $class, $method] = $matches;
        } elseif (is_string($controller)) {
            [$class, $method] = [$controller, self::INVOKE];
        } else {
            return null;
        }

        if (!class_exists($class) || !method_exists($class, $method)) {
            return null;
        }

        return [$class, $method];
    }

    private function isCallableArray(mixed $controller): bool
    {
        return is_array($controller)
            && count($controller) === 2
            && is_string($controller[0] ?? null)
            && is_string($controller[1] ?? null);
    }

    private function describeUnresolvedController(mixed $controller): string
    {
        return match (true) {
            is_string($controller) => $controller,
            is_array($controller) => 'array callable',
            default => 'unresolvable controller (' . get_debug_type($controller) . ')',
        };
    }

    /**
     * @return list<string>
     */
    private function listHttpMethods(Route $route): array
    {
        return $route->getMethods() === [] ? self::ALL_HTTP_METHODS : array_values($route->getMethods());
    }

    private function buildSamplePath(string $routeName, Route $route): string
    {
        return preg_replace_callback(
            self::PLACEHOLDER_PATTERN,
            fn (array $matches) => $this->buildPlaceholderValue($routeName, $route, $matches[1]),
            $route->getPath(),
        ) ?? $route->getPath();
    }

    private function buildPlaceholderValue(string $routeName, Route $route, string $name): string
    {
        $configured = null;
        foreach ($this->sampleParameters as $pattern => $values) {
            if (isset($values[$name]) && fnmatch($pattern, $routeName, FNM_NOESCAPE)) {
                $configured = $values[$name];
            }
        }
        if ($configured !== null) {
            return $configured;
        }

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
     * @param array{0: class-string, 1: string}|null $target
     * @return array{0: string|null, 1: string|null} the reason and the HTTP method concerned
     */
    private function findViolation(string $routeName, Route $route, array $httpMethods, ?array $target): array
    {
        if ($target !== null && $this->hasRealCheck(new ReflectionClass($target[0]), $target[1])) {
            return [null, null];
        }

        foreach ($httpMethods as $httpMethod) {
            $reason = $this->findReasonForHttpMethod($routeName, $route, $httpMethod);
            if ($reason !== null) {
                return [$reason, $httpMethod];
            }
        }

        return [null, null];
    }

    private function findReasonForHttpMethod(string $routeName, Route $route, string $httpMethod): ?string
    {
        $request = Request::create($this->buildSamplePath($routeName, $route), $httpMethod);

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

        $labels = array_map(fn (mixed $attribute) => $this->toLabel($attribute), $attributes);

        return array_diff($labels, self::PUBLIC_ATTRIBUTES) === [] ? self::REASON_PUBLIC : self::REASON_ROLE_USER;
    }

    private function isTrivialAttribute(mixed $attribute): bool
    {
        $label = $this->toLabel($attribute);

        return $label === self::ROLE_USER
            || in_array($label, self::PUBLIC_ATTRIBUTES, true)
            || str_starts_with($label, self::AUTHENTICATED_PREFIX);
    }

    private function toLabel(mixed $attribute): string
    {
        if (!is_string($attribute) && !$attribute instanceof Stringable) {
            return '';
        }

        $text = (string) $attribute;
        if (preg_match(self::EXPRESSION_ROLE_PATTERN, $text, $matches) === 1) {
            return $matches[1];
        }

        return preg_match(self::EXPRESSION_AUTHENTICATED_PATTERN, $text) === 1 ? self::AUTHENTICATED_LABEL : $text;
    }

    /**
     * Symfony reads #[IsGranted] on the concrete controller class only: attributes are not inherited.
     */
    private function hasRealCheck(ReflectionClass $controller, string $methodName): bool
    {
        $method = $controller->getMethod($methodName);

        return $this->hasRealIsGranted($method->getAttributes(IsGranted::class))
            || $this->hasRealIsGranted($controller->getAttributes(IsGranted::class))
            || $this->hasUnconditionalCheck($method, $controller, []);
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
     * Only statements of the method body itself count: a check nested in a block (if, foreach...) may not run.
     * A try block always runs, so it does not count as nesting.
     *
     * @param string[] $visited
     */
    private function hasUnconditionalCheck(ReflectionMethod $method, ReflectionClass $controller, array $visited): bool
    {
        $key = $method->getDeclaringClass()->getName() . '::' . $method->getName();
        $file = $method->getFileName();
        if (in_array($key, $visited, true) || $file === false) {
            return false;
        }
        $visited[] = $key;

        $length = $method->getEndLine() - $method->getStartLine() + 1;
        $source = implode('', array_slice(file($file) ?: [], $method->getStartLine() - 1, $length));
        $tokens = token_get_all('<?php ' . $source);
        $helpers = [];
        $depth = 0;
        $openBlocks = [];
        $tryPending = false;

        foreach ($tokens as $index => $token) {
            $tryPending = $tryPending || (is_array($token) && $token[0] === T_TRY);
            $change = $this->getDepthChange($token);
            if ($change > 0) {
                $openBlocks[] = $tryPending;
                $depth += $tryPending ? 0 : 1;
                $tryPending = false;
            } elseif ($change < 0) {
                $depth -= array_pop($openBlocks) === true ? 0 : 1;
            }

            if ($depth !== self::METHOD_BODY_DEPTH || !$this->isCall($tokens, $index)) {
                continue;
            }

            $name = $token[1];
            $call = $this->rebuildFrom($tokens, $index);
            if ($this->isGate($name, $call, $tokens, $index)) {
                return true;
            }
            if ($this->isCalledOnThis($tokens, $index) && $this->startsStatement($tokens, $index)) {
                $helpers[] = $name;
            }
        }

        foreach (array_unique($helpers) as $helperName) {
            if ($this->helperChecks($controller, $helperName, $visited)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, mixed> $tokens
     */
    private function isGate(string $name, string $call, array $tokens, int $index): bool
    {
        if (preg_match(self::TRIVIAL_CALL_PATTERN, $call) === 1) {
            return false;
        }

        if (in_array($name, self::DENY_CALLS, true)) {
            return $this->startsStatement($tokens, $index);
        }

        return $name === self::IS_GRANTED_CALL
            && $this->isNegatedIfCondition($tokens, $index)
            && preg_match(self::NEGATED_GUARD_PATTERN, $call) === 1;
    }

    private function getDepthChange(mixed $token): int
    {
        if ($token === '{' || (is_array($token) && in_array($token[0], self::OPENING_BRACE_TOKENS, true))) {
            return 1;
        }

        return $token === '}' ? -1 : 0;
    }

    /**
     * @param array<int, mixed> $tokens
     */
    private function isCall(array $tokens, int $index): bool
    {
        if (!is_array($tokens[$index]) || $tokens[$index][0] !== T_STRING) {
            return false;
        }

        for ($next = $index + 1; isset($tokens[$next]); $next++) {
            if (!is_array($tokens[$next]) || !in_array($tokens[$next][0], self::TRANSPARENT_TOKENS, true)) {
                return $tokens[$next] === '(';
            }
        }

        return false;
    }

    /**
     * @param array<int, mixed> $tokens
     */
    private function isCalledOnThis(array $tokens, int $index): bool
    {
        $operator = $this->findPreviousSignificant($tokens, $index);
        if ($operator === null || !is_array($tokens[$operator]) || $tokens[$operator][0] !== T_OBJECT_OPERATOR) {
            return false;
        }

        $owner = $this->findPreviousSignificant($tokens, $operator);

        return $owner !== null && is_array($tokens[$owner]) && $tokens[$owner][1] === self::THIS_VARIABLE;
    }

    /**
     * A call only runs for sure when it opens a statement: not after a condition, an operator or an arrow.
     *
     * @param array<int, mixed> $tokens
     */
    private function startsStatement(array $tokens, int $index): bool
    {
        $before = $this->findPreviousSignificant($tokens, $this->findChainStart($tokens, $index));

        return $before !== null && in_array($tokens[$before], self::STATEMENT_END_TOKENS, true);
    }

    /**
     * Matches `if (!$this->isGranted(...` at the beginning of a statement.
     *
     * @param array<int, mixed> $tokens
     */
    private function isNegatedIfCondition(array $tokens, int $index): bool
    {
        $negation = $this->findPreviousSignificant($tokens, $this->findChainStart($tokens, $index));
        $parenthesis = $negation === null ? null : $this->findPreviousSignificant($tokens, $negation);
        $keyword = $parenthesis === null ? null : $this->findPreviousSignificant($tokens, $parenthesis);
        if ($negation === null || $keyword === null || $tokens[$negation] !== '!' || $tokens[$parenthesis] !== '(') {
            return false;
        }

        return is_array($tokens[$keyword])
            && $tokens[$keyword][0] === T_IF
            && $this->startsStatement($tokens, $keyword);
    }

    private function isChainToken(mixed $token): bool
    {
        return is_array($token) && in_array($token[0], self::CHAIN_TOKENS, true);
    }

    /**
     * @param array<int, mixed> $tokens
     */
    private function findChainStart(array $tokens, int $index): int
    {
        $start = $index;
        while ($start > 0 && $this->isChainToken($tokens[$start - 1])) {
            $start--;
        }
        while (is_array($tokens[$start]) && $tokens[$start][0] === T_WHITESPACE) {
            $start++;
        }

        return $start;
    }

    /**
     * @param array<int, mixed> $tokens
     */
    private function findPreviousSignificant(array $tokens, int $index): ?int
    {
        for ($previous = $index - 1; $previous >= 0; $previous--) {
            if (!is_array($tokens[$previous]) || !in_array($tokens[$previous][0], self::TRANSPARENT_TOKENS, true)) {
                return $previous;
            }
        }

        return null;
    }

    /**
     * @param array<int, mixed> $tokens
     */
    private function rebuildFrom(array $tokens, int $index): string
    {
        $code = '';
        for ($position = $index; isset($tokens[$position]); $position++) {
            $token = $tokens[$position];
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
    private function helperChecks(ReflectionClass $controller, string $helperName, array $visited): bool
    {
        if (!$controller->hasMethod($helperName)) {
            return false;
        }

        $helper = $controller->getMethod($helperName);

        return !str_starts_with($helper->getDeclaringClass()->getName(), self::FRAMEWORK_NAMESPACE)
            && $this->hasUnconditionalCheck($helper, $controller, $visited);
    }
}
