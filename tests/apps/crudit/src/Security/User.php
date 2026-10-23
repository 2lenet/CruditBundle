<?php

declare(strict_types=1);

namespace App\Security;

use Lle\CruditBundle\Contracts\CruditUserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * User of the test application that gives its name, role and email to the user menu (CruditUserInterface).
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface, CruditUserInterface
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        private string $identifier,
        private string $displayName,
        private ?string $roleLabel,
        private ?string $email,
        private array $roles,
    ) {
    }

    public function getUserIdentifier(): string
    {
        return $this->identifier;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getPassword(): string
    {
        return $this->identifier;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function getRoleLabel(): ?string
    {
        return $this->roleLabel;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }
}
