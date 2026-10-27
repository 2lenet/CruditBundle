<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Two users, their password is their identifier (http_basic, for the smoke test): alice gives her name, role and
 * email to the user menu and can impersonate; bob gives his name only.
 *
 * @implements UserProviderInterface<User|PlainUser>
 */
class UserProvider implements UserProviderInterface
{
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        return match ($identifier) {
            'alice' => new User('alice', 'Alice Martin', 'Administrator', 'alice@example.org', ['ROLE_ADMIN', 'ROLE_ALLOWED_TO_SWITCH']),
            'bob' => new PlainUser('bob', 'Bob Durand'),
            default => throw new UserNotFoundException(),
        };
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return \in_array($class, [User::class, PlainUser::class], true);
    }
}
