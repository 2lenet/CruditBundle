<?php

declare(strict_types=1);

namespace App\Controller;

use App\Crudit\Config\ClientCrudConfig;
use Lle\CruditBundle\Controller\AbstractCrudController;
use Lle\CruditBundle\Controller\TraitCrudController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/client')]
class ClientController extends AbstractCrudController
{
    use TraitCrudController;

    public function __construct(ClientCrudConfig $config)
    {
        $this->config = $config;
    }

    /**
     * The actions of the header and of the lines that only show their kind (lot 3.1.a): back to the page.
     */
    #[Route('/action/{action}', name: 'app_client_action')]
    public function action(Request $request, string $action): RedirectResponse
    {
        $this->addFlash('success', $action);

        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_client_index'));
    }
}
