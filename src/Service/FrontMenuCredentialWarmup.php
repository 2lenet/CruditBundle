<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Lle\CredentialBundle\Contracts\CredentialWarmupInterface;
use Lle\CredentialBundle\Repository\CredentialRepository;
use Lle\CredentialBundle\Service\CredentialWarmupTrait;
use Lle\CruditBundle\Registry\MenuRegistry;

class FrontMenuCredentialWarmup implements CredentialWarmupInterface
{
    use CredentialWarmupTrait;

    public function __construct(
        protected MenuRegistry $menuRegistry,
        protected CredentialRepository $credentialRepository,
        protected EntityManagerInterface $entityManager
    ) {
    }

    public function warmUp(): void
    {
        $rubrique = "Menu";
        $i = 0;
        foreach ($this->menuRegistry->getElements("") as $menuItem) {
            if ($menuItem->getRole()) {
                $this->checkAndCreateCredential(
                    $menuItem->getRole(),
                    $rubrique,
                    "Menu " . str_replace("menu.", "", $menuItem->getId()),
                    $i++
                );
            }
            foreach ($menuItem->getChildren() as $submenuItem) {
                if ($submenuItem->getRole()) {
                    $this->checkAndCreateCredential(
                        $submenuItem->getRole(),
                        $rubrique,
                        "↳ Sous menu " . str_replace("menu.", "", $submenuItem->getId()),
                        $i++
                    );
                }
            }
        }
    }
}
