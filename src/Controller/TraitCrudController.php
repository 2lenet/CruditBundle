<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Controller;

use Lle\CruditBundle\Action\ActionView;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Datasource\DatasourceParams;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\FieldView;
use Lle\CruditBundle\Exporter\Exporter;
use Lle\CruditBundle\Field\WorkflowField;
use Lle\CruditBundle\Filter\FilterState;
use Lle\CruditBundle\Resolver\FieldResolver;
use Lle\CruditBundle\Resolver\ResourceResolver;
use Lle\CruditBundle\Service\NavigationStack;
use Lle\CruditBundle\Workflow\WorkflowStates;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
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

        $stack = $request->hasSession()
            ? (json_decode($request->getSession()->get(NavigationStack::SESSION_KEY, '[]'), true) ?? [])
            : [];
        $backUrl = !empty($stack) ? end($stack) : null;

        if ($backUrl && str_contains($backUrl, 'show')) {
            if ($route = $request->attributes->get('_route')) {
                preg_match('/^app_crudit_(.+)_delete$/', $route, $matches);
                if ($matches && array_key_exists(1, $matches)) {
                    // explode is necessary in case your controller is in a subdirectory
                    $parts = explode('_', $matches[1]);
                    $entityName = end($parts);
                    if (str_contains($backUrl, '/' . $entityName . '/')) {
                        return $this->redirectToRoute($this->config->getRootRoute() . '_index');
                    } else {
                        // If we're in a sublist, add the sublist anchor to the url so that it remains in the correct sublist after deletion
                        return $this->redirect($backUrl . '#' . $matches[1] . 's');
                    }
                }
            }
        }

        return $this->redirectToRoute($this->config->getRootRoute() . "_index");
    }

    #[Route('/sort', methods: ['POST'])]
    public function sort(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_' . $this->config->getName() . '_EDIT');

        $sortField = $this->config->getSortableField();
        if ($sortField === null) {
            return new JsonResponse(['status' => 'ko'], Response::HTTP_FORBIDDEN);
        }

        $ids = json_decode($request->getContent(), true);
        if (!is_array($ids)) {
            return new JsonResponse(['sattus' => 'ko'], Response::HTTP_BAD_REQUEST);
        }

        $this->config->getDatasource()->applySort($ids, $sortField);

        return new JsonResponse(['status' => 'ok']);
    }

    #[Route('/autocomplete')]
    public function autocomplete(Request $request): Response
    {
        $dataSource = $this->config->getDatasource();
        $res = [];
        $offset = $request->query->getInt('offset');
        $limit = $request->query->getInt('limit');

        $rqParams = new DatasourceParams(
            $limit,
            $offset,
            []
        );
        $nbItems = $dataSource->autocompleteCountQuery($request->query->get("q", ""));

        $items = $dataSource->autocompleteQuery(
            urldecode($request->query->get("q", "")),
            $this->config->getDefaultAutocompleteSort() ?? $this->config->getDefaultSort(),
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

            $dataSource->fillFromData($item, $data, $request->query->all());
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

            if (!$dataSource->save($item)) {
                return new JsonResponse([
                    "status" => "ko",
                    "message" => $translator->trans("crudit.flash.error.eip.generic", [], "LleCruditBundle"),
                ], Response::HTTP_BAD_REQUEST);
            }

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

        $format = $request->query->get("format", "csv");
        $params = $this->config->getExportParams($format);

        $path = $exporter->export(
            $generator(),
            $format,
            $params,
            $totals,
        );

        $response = new BinaryFileResponse($path);
        $response->deleteFileAfterSend();

        $filename = $params->getFilename() ?? 'export';
        $disposition = HeaderUtils::makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename . '_' . (new \DateTime())->format('YmdHis') . $exporter->getFileExtension($format),
        );
        $response->headers->set('Content-Disposition', $disposition);

        $contentType = $exporter->getContentType($format);
        $response->headers->set('Content-Type', $contentType);

        return $response;
    }

    /**
     * Applies a transition of a workflow (field/workflow.html.twig, controller workflow): POST with its CSRF token, the
     * workflow `name` and the `transition`; the role ROLE_<WORKFLOW>_WF_<TRANSITION> and the guards (can()) are
     * checked. Asked in JSON, it answers the HTML of the field (`field`, found in the fields of the CRUD) and the
     * message; else a flash and back to the page.
     */
    #[Route('/workflow/{id}', methods: ['POST'])]
    public function workflowTransition(
        Request $request,
        WorkflowStates $workflowStates,
        FieldResolver $fieldResolver,
        TranslatorInterface $translator,
        string $id,
    ): Response {
        $json = in_array('application/json', $request->getAcceptableContentTypes(), true);
        $fail = function (string $message, int $status) use ($json, $request, $translator): Response {
            $message = $translator->trans($message, [], 'LleCruditBundle');
            if ($json) {
                return new JsonResponse(['status' => 'ko', 'message' => $message], $status);
            }
            $this->addFlash('danger', $message);

            return $this->redirectToReferrer($request);
        };

        $dataSource = $this->config->getDatasource();
        $item = $dataSource->get($id);
        if ($item === null) {
            return $fail('crudit.flash.error.resource_not_found', Response::HTTP_NOT_FOUND);
        }

        if (!$this->isCsrfTokenValid('crudit_workflow_' . $id, (string) $request->request->get('_token'))) {
            return $fail('crudit.workflow.expired', Response::HTTP_FORBIDDEN);
        }

        $transition = (string) $request->request->get('transition');
        try {
            $workflow = $workflowStates->workflow($item, $request->request->get('name') ?: null);
        } catch (\InvalidArgumentException) {
            return $fail('crudit.workflow.error', Response::HTTP_BAD_REQUEST);
        }

        if (!$workflowStates->isGranted($workflow->getName(), $transition)) {
            return $fail('crudit.workflow.forbidden', Response::HTTP_FORBIDDEN);
        }

        if ($transition === '' || !$workflow->can($item, $transition)) {
            return $fail('crudit.workflow.unavailable', Response::HTTP_CONFLICT);
        }

        $label = fn (object $subject): string => implode(', ', array_map(
            fn (int|string $place): string => $translator->trans('wf.state.' . $place),
            array_keys($workflow->getMarking($subject)->getPlaces()),
        ));
        $from = $label($item);
        $workflow->apply($item, $transition);
        if (!$dataSource->save($item)) {
            return $fail('crudit.workflow.error', Response::HTTP_BAD_REQUEST);
        }

        $entity = ActionView::labelOf($item, $id);
        $parameters = ['%entity%' => $entity, '%from%' => $from, '%to%' => $label($item)];
        $message = $translator->trans(
            $entity !== '' ? 'crudit.workflow.success_entity' : 'crudit.workflow.success',
            $parameters,
            'LleCruditBundle',
        );

        if (!$json) {
            $this->addFlash('success', $message);

            return $this->redirectToReferrer($request);
        }

        $field = $this->findWorkflowField((string) $request->request->get('field'), $workflow->getName());

        return new JsonResponse([
            'status' => 'ok',
            'message' => $message,
            'html' => $fieldResolver->resolveView($field, $item, $dataSource, $this->config)->getValue(),
        ]);
    }

    /**
     * The WorkflowField of the CRUD named so (list, then record, to keep its options and its template), else a new
     * one on the workflow.
     */
    private function findWorkflowField(string $name, string $workflowName): Field
    {
        foreach ([CrudConfigInterface::INDEX, CrudConfigInterface::SHOW] as $key) {
            foreach ($this->config->getFields($key) as $field) {
                foreach (is_array($field) ? $field : [$field] as $candidate) {
                    if ($candidate instanceof Field && $candidate->getName() === $name) {
                        return $candidate;
                    }
                }
            }
        }

        return Field::new($name, WorkflowField::class, ['name' => $workflowName]);
    }

    private function getResource(Request $request, $allowCreate = true): ?object
    {
        $dataSource = $this->config->getDatasource();
        $resource = null;

        if ($request->attributes->get("id")) {
            $resource = $dataSource->get($request->attributes->get("id"));
        } elseif ($allowCreate) {
            $resource = $dataSource->newInstance();
        }

        return $resource;
    }

    protected function redirectToReferrer(Request $request, string $default = "/"): RedirectResponse
    {
        $stack = $request->hasSession()
            ? (json_decode($request->getSession()->get(NavigationStack::SESSION_KEY, '[]'), true) ?? [])
            : [];
        $url = !empty($stack) ? end($stack) : $default;

        return $this->redirect($url);
    }
}
