<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

/**
 * What the user menu of the header shows about the user (initials, name, role, email). Optional: on a user without
 * it, the menu shows its name only ((string) $user, else its identifier).
 *
 * The project decides what it shows, for example with CredentialBundle:
 *
 *     public function getDisplayName(): string { return $this->prenom . ' ' . $this->nom; }
 *     public function getRoleLabel(): ?string { return $this->profil?->getLabel(); }
 *     // getEmail(): ?string, already there
 */
interface CruditUserInterface
{
    /**
     * Name shown in the header and in the menu ("Claire Martin"); its initials are in the avatar.
     */
    public function getDisplayName(): string;

    /**
     * Role shown under the name ("Administrator"), already translated; null: no role shown.
     */
    public function getRoleLabel(): ?string;

    /**
     * Email shown in the menu; null: none.
     */
    public function getEmail(): ?string;
}
