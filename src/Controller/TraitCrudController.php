<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Controller;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Column;
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
        $this->denyAccessUnlessGranted('ROLE_' . $this->config->getName() . '_SHOW');

        $views = $this->getBrickBuilder()->build($this->config, CrudConfigInterface::SHOW);
        $response = $this->render('@LleCrudit/crud/index.html.twig', ['views' => $views]);

        return $this->getBrickResponseCollector()->handle($request, $response);
    }


    /**
     * @Route("/edit/{id}")
     */
    public function edit(Request $request, $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_' . $this->config->getName() . '_EDIT');

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
        $this->denyAccessUnlessGranted('ROLE_' . $this->config->getName() . '_DELETE');

        $dataSource = $this->config->getDatasource();
        $resource = $this->getResource($request, false);

        $dataSource->delete($dataSource->getIdentifier($resource));

        return $this->index($request);
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
            [], []
        );
        $nb_items = $dataSource->count_query("libelle", $request->query->get("q", ""));

        $items = $dataSource->query(
            "libelle",
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
                "total_count" => $nb_items,
                "next_offset" => $offset + $limit,
                "incomplete_results" => false,
                "items" => $res,
            ]
        );
    }

    /**
     * @Route("/editdata/{id}")
     */
    public function editdata(Request $request, $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_' . $this->config->getName() . '_EDIT');

        $dataSource = $this->config->getDatasource();
        $item = $dataSource->get($id);

        if ($item) {
            $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
                ->getPropertyAccessor();

            $reflection = new \ReflectionClass($item);
            $annotationReader = new AnnotationReader();

            $data = json_decode($request->request->get("data", []), true);

            foreach ($data as $field => $value) {
                if ($field === "id") {
                    continue;
                }

                if ($value === "") {
                    $value = null;
                } else {
                    $mapping = $annotationReader->getPropertyAnnotation(
                        $reflection->getProperty($field),
                        Column::class
                    );

                    switch ($mapping->type) {
                        case "date":
                        case "datetime":
                            $value = new \DateTime($value);
                            break;
                        case "integer":
                        case "smallint":
                            $value = (int)$value;
                            break;
                        case "float":
                            $value = (float)$value;
                            break;
                        case "string":
                        case "text":
                        case "decimal":
                        default:
                            // do nothing
                    }
                }

                $propertyAccessor->setValue($item, $field, $value);
            }

            $dataSource->save($item);

            return new JsonResponse(["status" => "ok"]);
        }

        return new JsonResponse(["status" => "ko"], Response::HTTP_BAD_REQUEST);
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
