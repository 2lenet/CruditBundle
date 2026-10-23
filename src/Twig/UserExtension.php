<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Twig;

use Lle\CruditBundle\Contracts\CruditUserInterface;
use Lle\CruditBundle\Routing\HomeUrl;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Impersonate\ImpersonateUrlGenerator;
use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator;
use Twig\Attribute\AsTwigFunction;

/**
 * The user of the header (layout/header/_user_menu, _impersonation):
 * - crudit_user(): null without user, else {name, role, email}, from CruditUserInterface when the user implements it,
 *   else its name only ((string) $user, else its identifier);
 * - crudit_impersonation(): during an impersonation (switch_user), {name, exit}: the name of the user who
 *   impersonates and the way back (impersonation_exit_path() of Symfony, to the route exit_impersonation_path, else to
 *   the root of the application: a missing route must not break every page); else null;
 * - crudit_logout(): the form of the logout, {action, fields}: the path of the logout of the firewall and, with
 *   `enable_csrf`, its CSRF field (the name and the token configured on the firewall), in the body of the POST, not in
 *   the address (logs). Null when the firewall has no logout: the menu has no logout then, and it is logged.
 */
class UserExtension
{
    public function __construct(
        protected TokenStorageInterface $tokenStorage,
        protected ?LogoutUrlGenerator $logoutUrlGenerator = null,
        protected LoggerInterface $logger = new NullLogger(),
        protected ?ImpersonateUrlGenerator $impersonateUrlGenerator = null,
        protected ?UrlGeneratorInterface $urlGenerator = null,
        protected ?HomeUrl $homeUrl = null,
        protected string $exitRoute = 'homepage',
    ) {
    }

    /**
     * @return array{name: string, role: ?string, email: ?string}|null
     */
    #[AsTwigFunction('crudit_user')]
    public function user(): ?array
    {
        $user = $this->tokenStorage->getToken()?->getUser();
        if ($user === null) {
            return null;
        }

        if ($user instanceof CruditUserInterface) {
            return ['name' => $user->getDisplayName(), 'role' => $user->getRoleLabel(), 'email' => $user->getEmail()];
        }

        return ['name' => $this->name($user), 'role' => null, 'email' => null];
    }

    /**
     * @return array{name: string, exit: string}|null
     */
    #[AsTwigFunction('crudit_impersonation')]
    public function impersonation(): ?array
    {
        $token = $this->tokenStorage->getToken();
        $user = $token instanceof SwitchUserToken ? $token->getOriginalToken()->getUser() : null;
        if ($user === null) {
            return null;
        }

        try {
            $target = $this->urlGenerator?->generate($this->exitRoute);
        } catch (RoutingException) {
            $target = null;
        }
        $target ??= $this->homeUrl?->url();

        return ['name' => $this->name($user), 'exit' => $this->impersonateUrlGenerator?->generateExitPath($target) ?? (string) $target];
    }

    /**
     * @return array{action: string, fields: array<string, string>}|null
     */
    #[AsTwigFunction('crudit_logout')]
    public function logout(): ?array
    {
        try {
            $path = $this->logoutUrlGenerator?->getLogoutPath();
        } catch (\LogicException $exception) {
            // No logout on the firewall (InvalidArgumentException), no request
            // debug: a firewall without logout (SSO, stateless) is a choice, the log must not grow at each page
            $this->logger->debug('No logout in the user menu: {message} (security.firewalls.<name>.logout).', [
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            return null;
        }
        if ($path === null) {
            return null;
        }

        $query = (string) parse_url($path, \PHP_URL_QUERY);
        parse_str($query, $fields);

        $hidden = [];
        foreach ($fields as $name => $value) {
            if (\is_string($value)) {
                $hidden[(string) $name] = $value;
            }
        }

        return ['action' => $query === '' ? $path : substr($path, 0, -\strlen($query) - 1), 'fields' => $hidden];
    }

    private function name(UserInterface $user): string
    {
        if ($user instanceof CruditUserInterface) {
            return $user->getDisplayName();
        }

        // An empty __toString() (it happens) gives the identifier
        $name = $user instanceof \Stringable ? trim((string) $user) : '';

        return $name !== '' ? $name : $user->getUserIdentifier();
    }
}
