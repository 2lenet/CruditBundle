<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Application de démonstration sans authentification : tout est autorisé (rôles de Crudit compris).
 * À retirer le jour où une authentification est ajoutée.
 */
class AllowAllVoter implements VoterInterface
{
    public function vote(TokenInterface $token, mixed $subject, array $attributes, ?Vote $vote = null): int
    {
        return self::ACCESS_GRANTED;
    }
}
