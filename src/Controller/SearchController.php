<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Controller;

use Lle\CruditBundle\Search\GlobalSearch;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The route of the global search of the layout (command palette): GET /crudit/search?q=<text>, which answers
 * {"groups": [{"id", "label", "results": [{"id", "title", "subtitle", "url"}]}]}. Imported by the project
 * (`@LleCruditBundle/Resources/config/routes.yaml`, UPGRADE-2.0.md).
 */
class SearchController
{
    public function __construct(private readonly GlobalSearch $search)
    {
    }

    #[Route('/crudit/search', name: 'lle_crudit_search', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $response = new JsonResponse(['groups' => $this->search->search($request->query->getString('q'))]);
        // What a user may see: neither kept by a shared cache nor by the browser.
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }
}
