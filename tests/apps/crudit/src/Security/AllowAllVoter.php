<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Application de démonstration sans authentification : tout est autorisé (rôles de Crudit compris), sauf le rôle
 * NOBODY, qui sert à tester ce que l'utilisateur n'a pas le droit de voir (menu).
 * À retirer le jour où une authentification est ajoutée.
 */
class AllowAllVoter implements VoterInterface
{
    public const NOBODY = 'ROLE_NOBODY';

    public function vote(TokenInterface $token, mixed $subject, array $attributes, ?Vote $vote = null): int
    {
        return \in_array(self::NOBODY, $attributes, true) ? self::ACCESS_DENIED : self::ACCESS_GRANTED;
    }
}
