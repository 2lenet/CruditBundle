<?php

declare(strict_types=1);

namespace phpunit\Unit\Twig;

use Lle\CruditBundle\Contracts\CruditUserInterface;
use Lle\CruditBundle\Twig\UserExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Lle\CruditBundle\Routing\HomeUrl;
use Psr\Log\AbstractLogger;
use Psr\Log\NullLogger;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Impersonate\ImpersonateUrlGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator;

/**
 * The user of the header: CruditUserInterface when the user implements it, else its name only; the user who
 * impersonates during an impersonation.
 */
class UserExtensionTest extends TestCase
{
    public function testNoUser(): void
    {
        $extension = new UserExtension(new TokenStorage());

        self::assertNull($extension->user());
        self::assertNull($extension->impersonation());
    }

    public function testCruditUser(): void
    {
        $extension = $this->extension(new UsernamePasswordToken($this->cruditUser(), 'main', ['ROLE_ADMIN']));

        self::assertSame(['name' => 'Alice Martin', 'role' => 'Administrator', 'email' => 'alice@example.org'], $extension->user());
        self::assertNull($extension->impersonation());
    }

    public function testUserWithoutTheInterfaceGivesItsNameOnly(): void
    {
        $user = $this->stringableUser('bob', 'Bob Durand');

        self::assertSame(['name' => 'Bob Durand', 'role' => null, 'email' => null], $this->extension(new UsernamePasswordToken($user, 'main'))->user());
    }

    /**
     * InMemoryUser has no __toString(); an empty one gives the identifier too.
     */
    public function testNameFallsBackOnTheIdentifier(): void
    {
        self::assertSame('dave', $this->extension(new UsernamePasswordToken(new InMemoryUser('dave', null), 'main'))->user()['name'] ?? null);
        self::assertSame('carol', $this->extension(new UsernamePasswordToken($this->stringableUser('carol', ' '), 'main'))->user()['name'] ?? null);
    }

    /**
     * The user who impersonates and the way back: the route exit_impersonation_path, else the root of the
     * application (a missing route must not break every page).
     */
    #[DataProvider('exitRoutes')]
    public function testImpersonation(string $exitRoute, string $exit): void
    {
        $original = new UsernamePasswordToken($this->cruditUser(), 'main', ['ROLE_ALLOWED_TO_SWITCH']);
        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken(new SwitchUserToken(new InMemoryUser('bob', null), 'main', [], $original));
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(static fn (string $name): string => $name === 'app_home' ? '/home' : throw new RouteNotFoundException($name));
        $impersonate = $this->createStub(ImpersonateUrlGenerator::class);
        $impersonate->method('generateExitPath')->willReturnCallback(static fn (?string $target): string => $target . '?_switch_user=_exit');
        $requestStack = new RequestStack();
        $requestStack->push(Request::create('/gestio/page', server: ['SCRIPT_FILENAME' => '/app/public/index.php', 'SCRIPT_NAME' => '/gestio/index.php']));

        $extension = new UserExtension($tokenStorage, null, new NullLogger(), $impersonate, $urlGenerator, new HomeUrl($requestStack), $exitRoute);

        self::assertSame('bob', $extension->user()['name'] ?? null);
        self::assertSame(['name' => 'Alice Martin', 'exit' => $exit], $extension->impersonation());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function exitRoutes(): iterable
    {
        yield 'the route of the configuration' => ['app_home', '/home?_switch_user=_exit'];
        yield 'a missing route: the root' => ['homepage', '/gestio/?_switch_user=_exit'];
    }

    /**
     * The path and the CSRF field of the firewall (name and id configured), in the body: not in the address.
     */
    public function testLogoutWithTheCsrfFieldOfTheFirewall(): void
    {
        $csrf = $this->createStub(CsrfTokenManagerInterface::class);
        $csrf->method('getToken')->willReturnCallback(static fn (string $id): CsrfToken => new CsrfToken($id, 'token-of-' . $id));
        $generator = new LogoutUrlGenerator($this->requestStack());
        $generator->registerListener('main', '/logout', 'my_logout', 'token', $csrf);
        $generator->setCurrentFirewall('main');

        self::assertSame(
            ['action' => '/logout', 'fields' => ['token' => 'token-of-my_logout']],
            (new UserExtension(new TokenStorage(), $generator))->logout(),
        );
    }

    public function testLogoutWithoutCsrf(): void
    {
        $generator = new LogoutUrlGenerator($this->requestStack());
        $generator->registerListener('main', '/logout', null, null);
        $generator->setCurrentFirewall('main');

        self::assertSame(['action' => '/logout', 'fields' => []], (new UserExtension(new TokenStorage(), $generator))->logout());
    }

    /**
     * A firewall without logout: no logout in the menu, a debug message, no error on the page.
     */
    public function testNoLogoutOnTheFirewall(): void
    {
        $generator = new LogoutUrlGenerator(new RequestStack());
        $generator->setCurrentFirewall('main');
        $logger = new class extends AbstractLogger {
            /** @var list<string> */
            public array $messages = [];

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->messages[] = $level . ' ' . $message;
            }
        };

        self::assertNull((new UserExtension(new TokenStorage(), $generator, $logger))->logout());
        self::assertCount(1, $logger->messages);
        self::assertStringStartsWith('debug No logout in the user menu', $logger->messages[0]);
    }

    private function requestStack(): RequestStack
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create('/'));

        return $requestStack;
    }

    private function extension(TokenInterface $token): UserExtension
    {
        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($token);

        return new UserExtension($tokenStorage);
    }

    /**
     * @param non-empty-string $identifier
     */
    private function stringableUser(string $identifier, string $name): UserInterface
    {
        return new class ($identifier, $name) implements UserInterface, \Stringable {
            /**
             * @param non-empty-string $identifier
             */
            public function __construct(
                private string $identifier,
                private string $name,
            ) {
            }

            public function __toString(): string
            {
                return $this->name;
            }

            public function getUserIdentifier(): string
            {
                return $this->identifier;
            }

            public function getRoles(): array
            {
                return [];
            }

            public function eraseCredentials(): void
            {
            }
        };
    }

    private function cruditUser(): UserInterface
    {
        return new class implements UserInterface, CruditUserInterface {
            public function getDisplayName(): string
            {
                return 'Alice Martin';
            }

            public function getRoleLabel(): string
            {
                return 'Administrator';
            }

            public function getEmail(): string
            {
                return 'alice@example.org';
            }

            public function getUserIdentifier(): string
            {
                return 'alice';
            }

            public function getRoles(): array
            {
                return [];
            }

            public function eraseCredentials(): void
            {
            }
        };
    }
}
