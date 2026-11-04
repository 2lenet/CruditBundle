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

    /**
     * The batch actions of the list (lot 3.1.c): they receive `ids` (separated by commas) and `all_page`, in the address
     * (a link) or in the fields of the form (POST, the form of the action has them as fields `ids` and `all_page`, in `client_batch` or, for a free modal, at the root).
     */
    #[Route('/batch/{action}', name: 'app_client_batch')]
    public function batch(Request $request, string $action): RedirectResponse
    {
        $data = $request->request->all()['client_batch'] ?? ($request->request->all() ?: $request->query->all());
        $this->addFlash('success', sprintf('%s: ids=%s all_page=%s', $action, $data['ids'] ?? '', $data['all_page'] ?? ''));

        return $this->redirect($this->generateUrl('app_client_index'));
    }
}
