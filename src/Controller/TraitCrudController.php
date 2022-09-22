<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Controller;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Datasource\DatasourceParams;
use Lle\CruditBundle\Exception\CruditException;
use Lle\CruditBundle\Exporter\Exporter;
use Lle\CruditBundle\Filter\FilterState;
use Lle\CruditBundle\Resolver\ResourceResolver;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Workflow\Registry;
use Symfony\Contracts\Translation\TranslatorInterface;

trait TraitCrudController
{
    /**
     * @var CrudConfigInterface
     */
    protected $config;

    /**
     * @Route("/")
     */
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_' . $this->config->getName() . '_INDEX');

        $views = $this->getBrickBuilder()->build($this->config, CrudConfigInterface::INDEX);
        $response = $this->render('@LleCrudit/crud/index.html.twig', ['views' => $views]);

        return $this->getBrickResponseCollector()->handle($request, $response);
    }

    /**
     * @Route("/show/{id}")
     */
    public function show(Request $request, $id): Response
    {
        $resource = $this->getResource($request, false);

        $this->denyAccessUnlessGranted('ROLE_' . $this->config->getName() . '_SHOW', $resource);

        $views = $this->getBrickBuilder()->build($this->config, CrudConfigInterface::SHOW);
        $response = $this->render('@LleCrudit/crud/index.html.twig', ['views' => $views]);

        return $this->getBrickResponseCollector()->handle($request, $response);
    }


    /**
     * @Route("/edit/{id}")
     */
    public function edit(Request $request, $id): Response
    {
        $resource = $this->getResource($request, false);

        $this->denyAccessUnlessGranted('ROLE_' . $this->config->getName() . '_EDIT', $resource);

        $views = $this->getBrickBuilder()->build($this->config, CrudConfigInterface::EDIT);
        $response = $this->render('@LleCrudit/crud/index.html.twig', ['views' => $views]);

        return $this->getBrickResponseCollector()->handle($request, $response);
    }

    /**
     * @Route("/new")
     */
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_' . $this->config->getName() . '_NEW');

        $views = $this->getBrickBuilder()->build($this->config, CrudConfigInterface::NEW);
        $response = $this->render('@LleCrudit/crud/index.html.twig', ['views' => $views]);

        return $this->getBrickResponseCollector()->handle($request, $response);
    }

    /**
     * @Route("/delete/{id}")
     */
    public function delete(Request $request): Response
    {
        $resource = $this->getResource($request, false);

        $this->denyAccessUnlessGranted('ROLE_' . $this->config->getName() . '_DELETE', $resource);

        $dataSource = $this->config->getDatasource();
        $dataSource->delete($dataSource->getIdentifier($resource));

        return $this->redirectToRoute($this->config->getRootRoute() . "_index");
    }

    /**
     * @Route("/autocomplete")
     */
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
            $request->query->get("q", ""),
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

    /**
     * @Route("/editdata/{id}")
     */
    public function editdata($id, Request $request, TranslatorInterface $translator): Response
    {
        try {
            $this->denyAccessUnlessGranted('ROLE_' . $this->config->getName() . '_EDIT');

            $dataSource = $this->config->getDatasource();

            if ($dataSource->editData($id, $request) === "ok") {
                return new JsonResponse(["status" => "ok"]);
            }

            return new JsonResponse([
                "status" => "ko",
                "message" => $translator->trans("crudit.flash.error.eip.bad_request", [], "LleCruditBundle"),
            ], Response::HTTP_BAD_REQUEST);
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

    /**
     * @Route("/export")
     */
    public function export(
        Request $request,
        FilterState $filterState,
        Exporter $exporter,
        ResourceResolver $resolver
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

        $format = $request->get("format", "csv");

        return $exporter->export(
            $generator(),
            $format,
            $this->config->getExportParams($format)
        );
    }

    /**
     * @Route("/api/{pageKey}")
     */
    public function api(Request $request): Response
    {
        $views = $this->getBrickBuilder()->build($this->config, $request->get('pageKey'));

        return new JsonResponse($this->getSerializer()->normalize($views));
    }

    /**
     * @Route("/brick/{idBrick}.{_format}", format="json", requirements={"_format": "html|json"})
     */
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

    /**
     * @Route("/brick/data/{idBrick}")
     */
    public function brickData(Request $request): Response
    {
        $view = $this->getBrickBuilder()->getView(
            $this->config,
            $request->get('idBrick')
        );

        return new JsonResponse($this->getSerializer()->normalize($view->getData()));
    }

    /**
     * @Route("/brick/config/{idBrick}")
     */
    public function brickConfig(Request $request): Response
    {
        $view = $this->getBrickBuilder()->getView(
            $this->config,
            $request->get('idBrick')
        );

        return new JsonResponse($this->getSerializer()->normalize($view->getConfig()));
    }

    /**
     * @Route("/workflow/{id}")
     */
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

    private function getResource(Request $request, $allowCreate = true): object
    {
        $dataSource = $this->config->getDatasource();
        $resource = null;

        if ($request->get("id")) {
            $resource = $dataSource->get($request->get("id"));
        } elseif ($allowCreate) {
            $resource = $dataSource->newInstance();
        }

        if ($resource === null) {
            throw new CruditException(
                sprintf(
                    "Resource %s of class %s not found",
                    $request->get("id", "NO_ID"),
                    $dataSource->getClassName()
                )
            );
        }

        return $resource;
    }

    protected function redirectToReferrer(Request $request, $default = "/"): RedirectResponse
    {
        $url = $request->headers->get("referer", $default);

        return $this->redirect($url);
    }
}
