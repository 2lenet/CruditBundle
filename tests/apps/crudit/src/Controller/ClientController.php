<?php

declare(strict_types=1);

namespace App\Controller;

use App\Crudit\Config\ClientCrudConfig;
use Lle\CruditBundle\Controller\AbstractCrudController;
use Lle\CruditBundle\Controller\TraitCrudController;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/client')]
class ClientController extends AbstractCrudController
{
    use TraitCrudController;

    public function __construct(ClientCrudConfig $config)
    {
        $this->config = $config;
    }
}
