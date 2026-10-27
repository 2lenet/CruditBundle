<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * User without CruditUserInterface: the user menu shows its name only (__toString()).
 */
class PlainUser implements UserInterface, PasswordAuthenticatedUserInterface, \Stringable
{
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
        return ['ROLE_USER'];
    }

    public function getPassword(): string
    {
        return $this->identifier;
    }
}
