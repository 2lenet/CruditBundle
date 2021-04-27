<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Controller;

use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Dto\Action\DeleteAction;
use Lle\CruditBundle\Exception\CruditException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
        $this->denyAccessUnlessGranted('ROLE_' . $this->config->getName() . '_LIST');

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
        $dataSource->flush();

        return $this->redirectToReferrer($request);
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

        } else if ($allowCreate) {
            $resource = $dataSource->newInstance();
        }

        if ($resource === null) {
            throw new CruditException(
                sprintf("Resource %s of class %s not found",
                    $request->get("id", "NO_ID"),
                    $dataSource->getClassName())
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
