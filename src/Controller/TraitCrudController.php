<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Controller;

use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Datasource\DatasourceParams;
use Lle\CruditBundle\Dto\FieldView;
use Lle\CruditBundle\Exception\CruditException;
use Lle\CruditBundle\Exporter\Exporter;
use Lle\CruditBundle\Filter\FilterState;
use Lle\CruditBundle\Resolver\ResourceResolver;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Workflow\Registry;
use Symfony\Contracts\Translation\TranslatorInterface;

trait TraitCrudController
{
    protected CrudConfigInterface $config;

    #[Route('/')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_' . $this->config->getName() . '_INDEX');

        $views = $this->getBrickBuilder()->build($this->config, CrudConfigInterface::INDEX);
        $response = $this->render('@LleCrudit/crud/index.html.twig', ['views' => $views]);

        return $this->getBrickResponseCollector()->handle($request, $response);
    }

    #[Route('/show/{id}')]
    public function show(Request $request, $id): Response
    {
        $resource = $this->getResource($request, false);
        if (!$resource) {
            $this->addFlash('danger', 'crudit.flash.error.resource_not_found');

            return $this->redirectToRoute($this->config->getRootRoute() . "_index");
        }

        $this->denyAccessUnlessGranted('ROLE_' . $this->config->getName() . '_SHOW', $resource);

        $views = $this->getBrickBuilder()->build($this->config, CrudConfigInterface::SHOW);
        $response = $this->render('@LleCrudit/crud/index.html.twig', ['views' => $views]);

        return $this->getBrickResponseCollector()->handle($request, $response);
    }

    #[Route('/edit/{id}')]
    public function edit(Request $request, $id): Response
    {
        $resource = $this->getResource($request, false);
        if (!$resource) {
            $this->addFlash('danger', 'crudit.flash.error.resource_not_found');

            return $this->redirectToRoute($this->config->getRootRoute() . "_index");
        }

        $this->denyAccessUnlessGranted('ROLE_' . $this->config->getName() . '_EDIT', $resource);

        $views = $this->getBrickBuilder()->build($this->config, CrudConfigInterface::EDIT);
        $response = $this->render('@LleCrudit/crud/index.html.twig', ['views' => $views]);

        return $this->getBrickResponseCollector()->handle($request, $response);
    }

    #[Route('/new')]
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_' . $this->config->getName() . '_NEW');

        $views = $this->getBrickBuilder()->build($this->config, CrudConfigInterface::NEW);
        $response = $this->render('@LleCrudit/crud/index.html.twig', ['views' => $views]);

        return $this->getBrickResponseCollector()->handle($request, $response);
    }

    #[Route('/delete/{id}')]
    public function delete(Request $request): Response
    {
        $resource = $this->getResource($request, false);
        if (!$resource) {
            $this->addFlash('danger', 'crudit.flash.error.resource_not_found');

            return $this->redirectToRoute($this->config->getRootRoute() . "_index");
        }
        if (method_exists($resource, 'canDelete')) {
            $check = $resource->canDelete();

            if ($check === false || is_string($check)) {
                $this->addFlash('danger', 'crudit.flash.error.delete');

                return $this->redirectToRoute($this->config->getRootRoute() . "_index");
            }
        }

        $this->denyAccessUnlessGranted('ROLE_' . $this->config->getName() . '_DELETE', $resource);

        $dataSource = $this->config->getDatasource();
        $dataSource->delete($dataSource->getIdentifier($resource));

        $referer = $request->headers->get('referer');
        if ($referer && str_contains($referer, 'show')) {
            if ($route = $request->attributes->get('_route')) {
                preg_match('/^app_crudit_(.+)_delete$/', $route, $matches);
                if ($matches && array_key_exists(1, $matches)) {
                    if (str_contains($referer, '/' . $matches[1] . '/')) {
                        return $this->redirectToRoute($this->config->getRootRoute() . "_index");
                    } else {
                        // If we're in a sublist, add the sublist anchor to the url so that it remains in the correct sublist after deletion
                        return $this->redirect($request->headers->get('referer') . '#' . $matches[1] . 's');
                    }
                }
            }
        }

        return $this->redirectToRoute($this->config->getRootRoute() . "_index");
    }

    #[Route('/autocomplete')]
    public function autocomplete(Request $request): Response
    {
        $dataSource = $this->config->getDatasource();
        $res = [];
        $offset = intval($request->get('offset', 0));
        $limit = intval($request->get('limit', 0));

        $rqParams = new DatasourceParams(
            $limit,
            $offset,
            []
        );
        $nbItems = $dataSource->autocompleteCountQuery($request->query->get("q", ""));

        $items = $dataSource->autocompleteQuery(
            urldecode($request->query->get("q", "")),
            $this->config->getDefaultSort(),
            $rqParams
        );

        foreach ($items as $item) {
            $res[] = [
                "id" => $item->getId(),
                "text" => (string)$item,
            ];
        }

        return new JsonResponse(
            [
                "total_count" => $nbItems,
                "next_offset" => $offset + $limit,
                "incomplete_results" => false,
                "items" => $res,
            ]
        );
    }

    #[Route('/editdata/{id}')]
    public function editdata(string $id, Request $request, TranslatorInterface $translator, ValidatorInterface $validator): Response
    {
        try {
            $this->denyAccessUnlessGranted('ROLE_' . $this->config->getName() . '_EDIT');

            $dataSource = $this->config->getDatasource();

            $data = json_decode($request->request->get("data", "{}"), true);

            $item = $this->getResource($request);

            if (!$item) {
                return new JsonResponse([
                    "status" => "ko",
                    "message" => $translator->trans("crudit.flash.error.eip.bad_request", [], "LleCruditBundle"),
                ], Response::HTTP_BAD_REQUEST);
            }

            $dataSource->fillFromData($item, $data);
            $errors = $validator->validate($item);

            if (count($errors) > 0) {
                $message = '';
                foreach ($errors as $error) {
                    $message .= $error->getMessage() . ' ';
                }

                return new JsonResponse([
                    "status" => "ko",
                    "message" => $message,
                ], Response::HTTP_BAD_REQUEST);
            }

            $dataSource->save($item);

            return new JsonResponse([
                "status" => "ok",
                "fieldsToUpdate" => $this->config->fieldsToUpdate($id),
                'eipToUpdate' => $this->config->eipToUpdate($id),
            ]);
        } catch (AccessDeniedException $e) {
            return new JsonResponse([
                "status" => "ko",
                "message" => $translator->trans("crudit.flash.error.eip.access_denied", [], "LleCruditBundle"),
            ], Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => "ko",
                "message" => $translator->trans("crudit.flash.error.eip.generic", [], "LleCruditBundle"),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/export')]
    public function export(
        Request $request,
        FilterState $filterState,
        Exporter $exporter,
        ResourceResolver $resolver,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_' . $this->config->getName() . '_EXPORT');

        $datasource = $this->config->getDatasource();
        $dsParams = $this->config->getDatasourceParams($request);

        // we request all of the filtered resources, so no limit
        $dsParams->setLimit(0);
        // Get all ressources i.e ask export on page 2 you also need the results from page 1
        $dsParams->setOffset(0);
        $resources = $datasource->list($dsParams);

        $fields = $this->config->getFields(CrudConfigInterface::EXPORT);
        if (empty($fields)) {
            $fields = $this->config->getFields(CrudConfigInterface::INDEX);
        }

        $generator = function () use ($resources, $datasource, $fields, $resolver) {
            foreach ($resources as $resource) {
                yield ($resolver->resolve($resource, $fields, $datasource));
            }
        };

        $totals = [];
        if (count($this->config->getTotalFields()) > 0) {
            $dsParams = $this->config->getDatasourceParams($request);
            $dsParams->setCount($this->config->getDatasource()->count($dsParams));
            /** @var array $totalByField */
            $totalByField = $this->config->getDatasource()->getTotals($dsParams, $this->config->getTotalFields());

            $i = 0;
            $fieldViews = [];
            foreach ($this->config->getTotalFields() as $field) {
                $i++;

                $fieldView = new FieldView($field['field'], $totalByField[$i]);
                $fieldView->setOptions($field['field']->getOptions());

                $totals[] = [
                    'field' => $fieldView,
                    'total' => $totalByField[$i]
                ];
            }
        }

        $format = $request->get("format", "csv");

        return $exporter->export(
            $generator(),
            $format,
            $this->config->getExportParams($format),
            $totals
        );
    }

    #[Route('/api/{pageKey}')]
    public function api(Request $request): Response
    {
        $views = $this->getBrickBuilder()->build($this->config, $request->get('pageKey'));

        return new JsonResponse($this->getSerializer()->normalize($views));
    }

    #[Route('/brick/{idBrick}.{_format}', format: 'json', requirements: ['_format' => 'html|json'])]
    public function brick(Request $request): Response
    {
        $view = $this->getBrickBuilder()->getView(
            $this->config,
            $request->get('idBrick')
        );
        if ($request->get('_format') === 'json') {
            return new JsonResponse($this->getSerializer()->normalize($view));
        } else {
            return $this->render($view->getIndexTemplate(), ['view' => $view]);
        }
    }

    #[Route('/brick/data/{idBrick}')]
    public function brickData(Request $request): Response
    {
        $view = $this->getBrickBuilder()->getView(
            $this->config,
            $request->get('idBrick')
        );

        return new JsonResponse($this->getSerializer()->normalize($view->getData()));
    }

    #[Route('/brick/config/{idBrick}')]
    public function brickConfig(Request $request): Response
    {
        $view = $this->getBrickBuilder()->getView(
            $this->config,
            $request->get('idBrick')
        );

        return new JsonResponse($this->getSerializer()->normalize($view->getConfig()));
    }

    #[Route('/workflow/{id}')]
    public function workflowTransition(Request $request, Registry $wfRegistry, $id): Response
    {
        $transition = $request->get("transition");
        $dataSource = $this->config->getDatasource();
        $item = $dataSource->get($id);

        if ($item && $transition) {
            $roleTransition = str_replace("-", "_", strtoupper($transition));
            $this->denyAccessUnlessGranted(
                "ROLE_" . $this->config->getName() . "_WF_" . $roleTransition
            );

            foreach ($wfRegistry->all($item) as $wf) {
                if ($wf->can($item, $transition)) {
                    $wf->apply($item, $transition);
                    $dataSource->save($item);
                }
            }
        }

        return $this->redirectToReferrer($request);
    }

    private function getResource(Request $request, $allowCreate = true): ?object
    {
        $dataSource = $this->config->getDatasource();
        $resource = null;

        if ($request->get("id")) {
            $resource = $dataSource->get($request->get("id"));
        } elseif ($allowCreate) {
            $resource = $dataSource->newInstance();
        }

        return $resource;
    }

    protected function redirectToReferrer(Request $request, $default = "/"): RedirectResponse
    {
        $url = $request->headers->get("referer", $default);

        return $this->redirect($url);
    }
}
