<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Controller;

use Lle\CruditBundle\Action\ActionView;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Datasource\DatasourceParams;
use Lle\CruditBundle\Dto\FieldView;
use Lle\CruditBundle\Exception\ExporterException;
use Lle\CruditBundle\Exporter\DownloadCookie;
use Lle\CruditBundle\Exporter\Exporter;
use Lle\CruditBundle\Filter\FilterState;
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
use Symfony\Component\Workflow\Exception\LogicException as WorkflowLogicException;
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
                        // From a sublist: back to the record, in the tab it was in (?tab=<id> of the page sent from)
                        return $this->redirect(self::withTabOf($request, $backUrl));
                    }
                }
            }
        }

        return $this->redirectToRoute($this->config->getRootRoute() . "_index");
    }

    /**
     * The address of the record with the tab of the page the request comes from (its Referer, the address kept up to
     * date by the tabs: ?tab=<id>), when it is the same record.
     */
    private static function withTabOf(Request $request, string $backUrl): string
    {
        $referer = (string) $request->headers->get('referer');
        parse_str((string) parse_url($referer, \PHP_URL_QUERY), $query);
        $tab = $query['tab'] ?? null;
        if (!\is_string($tab) || '' === $tab || parse_url($referer, \PHP_URL_PATH) !== parse_url($backUrl, \PHP_URL_PATH)) {
            return $backUrl;
        }
        parse_str((string) parse_url($backUrl, \PHP_URL_QUERY), $backQuery);
        $path = (string) strtok($backUrl, '?#');

        return $path . '?' . http_build_query([...$backQuery, 'tab' => $tab]);
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
            $request->query->get("q", ""),
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
        TranslatorInterface $translator,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_' . $this->config->getName() . '_EXPORT');

        // only the formats the action offers (an old link, a format of a project that this list does not choose)
        $format = (string) $request->query->get("format", "csv");
        if (!in_array($format, $this->exportFormats($exporter), true)) {
            return $this->exportFailed($request, $translator);
        }

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

        $params = $this->config->getExportParams($format);

        try {
            $path = $exporter->export(
                $generator(),
                $format,
                $params,
                $totals,
            );
        } catch (ExporterException) {
            return $this->exportFailed($request, $translator);
        }

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

        // The dialog of the export waits for this cookie: the file is made, the download begins
        DownloadCookie::attach($request, $response);

        return $response;
    }

    /**
     * The formats that the page offers: those of every action of the list that points to the route of the export,
     * whatever its key (the skeleton adds the actions of a project with a numeric key; a list may have two buttons with
     * different formats), in a dropdown too, and that the user may use (the role of the button: a format of a button
     * reserved to ROLE_ADMIN is not asked by the URL by a user who only has the role EXPORT). An action with only an
     * URL (setUrl()) has no route: it is not looked at. No such action at all: the formats of the bundle. Some, but none that this user may use: no format.
     *
     * @return list<string>
     */
    private function exportFormats(Exporter $exporter): array
    {
        return $exporter->resolveActionsFormats(
            $this->config->getListActions(),
            $this->config->getPath(CrudConfigInterface::EXPORT)->getRoute(),
            fn (string $role): bool => $this->isGranted($role),
        );
    }

    /**
     * An export that could not be made. From the dialog (the request has a `download_token`) the answer goes into a hidden
     * frame: a small page, status 422, whose element `data-crudit-export-error` holds the message that the dialog shows.
     * A redirection with a flash would load the whole list in the frame, and that render would use the flash up. From a
     * link: the flash message on the list.
     */
    private function exportFailed(Request $request, TranslatorInterface $translator): Response
    {
        if (DownloadCookie::token($request) === null) {
            $this->addFlash('danger', 'crudit.flash.error.export');

            return $this->redirectToRoute($this->config->getRootRoute() . "_index");
        }

        $message = htmlspecialchars($translator->trans('crudit.flash.error.export', [], 'LleCruditBundle'), ENT_QUOTES);
        $response = new Response(
            '<!DOCTYPE html><html lang="' . htmlspecialchars($request->getLocale(), ENT_QUOTES) . '"><head><meta charset="utf-8">'
            . '<title>' . $message . '</title></head><body><p data-crudit-export-error>' . $message . '</p></body></html>',
            Response::HTTP_UNPROCESSABLE_ENTITY,
            ['Content-Type' => 'text/html; charset=UTF-8'],
        );
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }

    /**
     * Applies a transition of a workflow (field/workflow.html.twig, controller workflow): POST with its CSRF token
     * (crudit_workflow_<workflow>_<id>), the workflow `name` and the `transition`. Checked: the role
     * ROLE_<WORKFLOW>_WF_<TRANSITION> with the element as subject (a voter of the project decides the perimeter) and
     * the guards (can()). The roles of a screen (Field::setRole(), ROLE_<CRUD>_SHOW…) are rules of display: the
     * status may be changed wherever it is displayed.
     * Asked in JSON, it answers the message and the HTML of the status (field/workflow.html.twig rendered for the
     * element, wherever it was displayed: list, record, sublist, template of the project); `field` only names the
     * copies to replace in the page. Else a flash and back to the page.
     */
    #[Route('/workflow/{id}', methods: ['POST'])]
    public function workflowTransition(
        Request $request,
        WorkflowStates $workflowStates,
        TranslatorInterface $translator,
        string $id,
    ): Response {
        $json = $request->getPreferredFormat() === 'json';
        $fail = function (string $message, int $status) use ($json, $request, $translator): Response {
            $message = $translator->trans($message, [], 'LleCruditBundle');
            if ($json) {
                return new JsonResponse(['status' => 'ko', 'message' => $message], $status);
            }
            $this->addFlash('danger', $message);

            return $this->redirectToReferrer($request);
        };

        // The CSRF token is checked before the element is loaded, so an unknown id gets the same 403 as a known one (no
        // 404 that would tell which ids exist). Its name holds the workflow and the id: the token of intervention 12 is
        // not valid for invoice 12.
        $name = (string) $request->request->get('name');
        if (!$this->isCsrfTokenValid('crudit_workflow_' . $name . '_' . $id, (string) $request->request->get('_token'))) {
            return $fail('crudit.workflow.expired', Response::HTTP_FORBIDDEN);
        }

        $dataSource = $this->config->getDatasource();
        $item = $dataSource->get($id);
        if ($item === null) {
            return $fail('crudit.flash.error.resource_not_found', Response::HTTP_NOT_FOUND);
        }

        $transition = (string) $request->request->get('transition');
        if ($transition === '') {
            return $fail('crudit.workflow.unavailable', Response::HTTP_CONFLICT);
        }

        try {
            $workflow = $workflowStates->workflow($item, $name);
        } catch (\InvalidArgumentException) {
            return $fail('crudit.workflow.error', Response::HTTP_BAD_REQUEST);
        }

        if (!$workflowStates->isGranted($workflow->getName(), $transition, $item)) {
            return $fail('crudit.workflow.forbidden', Response::HTTP_FORBIDDEN);
        }

        // Without state, can() would put the element in the initial place (Workflow::getMarking())
        if ($workflowStates->places($workflow, $item) === [] || !$workflow->can($item, $transition)) {
            return $fail('crudit.workflow.unavailable', Response::HTTP_CONFLICT);
        }

        $label = fn (object $subject): string => implode(', ', array_map(
            fn (string $place): string => $translator->trans('wf.state.' . $place),
            $workflowStates->places($workflow, $subject),
        ));
        $from = $label($item);
        try {
            $workflow->apply($item, $transition);
        } catch (WorkflowLogicException) {
            // A listener of the workflow (transition, entered…) refused it, or another user changed the status between
            // can() and apply() (NotEnabledTransitionException)
            return $fail('crudit.workflow.unavailable', Response::HTTP_CONFLICT);
        }
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

        return new JsonResponse([
            'status' => 'ok',
            'message' => $message,
            'html' => $this->renderView('@LleCrudit/field/workflow.html.twig', [
                'resource' => $item,
                'value' => null,
                'options' => ['name' => $workflow->getName()],
                'view' => ['config' => $this->config, 'field' => ['name' => (string) $request->request->get('field')]],
            ]),
        ]);
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
