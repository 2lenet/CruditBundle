<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Controller;

use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

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
        $this->denyAccessUnlessGranted('ROLE_'.$this->config->getName().'_LIST');

        $views = $this->getBrickBuilder()->build($this->config, CrudConfigInterface::INDEX, $request);
        $response = $this->render('@LleCrudit/crud/index.html.twig', ['views' => $views]);
        return $this->getBrickResponseCollector()->handle($request, $response);
    }

    /**
     * @Route("/show/{id}")
     */
    public function show(Request $request, $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_'.$this->config->getName().'_SHOW');

        $views = $this->getBrickBuilder()->build($this->config, CrudConfigInterface::SHOW, $request);
        $response = $this->render('@LleCrudit/crud/index.html.twig', ['views' => $views]);
        return $this->getBrickResponseCollector()->handle($request, $response);
    }


    /**
     * @Route("/edit/{id}")
     */
    public function edit(Request $request, $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_'.$this->config->getName().'_EDIT');

        $views = $this->getBrickBuilder()->build($this->config, CrudConfigInterface::EDIT, $request);
        $response = $this->render('@LleCrudit/crud/index.html.twig', ['views' => $views]);
        return $this->getBrickResponseCollector()->handle($request, $response);
    }

    /**
     * @Route("/new")
     */
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_'.$this->config->getName().'_NEW');

        $views = $this->getBrickBuilder()->build($this->config, CrudConfigInterface::NEW, $request);
        $response = $this->render('@LleCrudit/crud/index.html.twig', ['views' => $views]);
        return $this->getBrickResponseCollector()->handle($request, $response);
    }


    /**
     * @Route("/api")
     */
    public function api(Request $request): Response
    {
        $views = $this->getBrickBuilder()->build($this->config, $request);
        return new JsonResponse($this->getSerializer()->normalize($views));
    }

    /**
     * @Route("/brick/{pageKey}/{id}")
     */
    public function brick(Request $request): Response
    {
        $view = $this->getBrickBuilder()->getView($this->config, $request->get('pageKey'),$request, $request->get('id'));
        return new JsonResponse($this->getSerializer()->normalize($view));
    }

    /**
     * @Route("/brick/api/{pageKey}/{id}")
     */
    public function brickApi(Request $request): Response
    {
        $view = $this->getBrickBuilder()->getView($this->config, $request->get('pageKey'),$request, $request->get('id'));
        return new JsonResponse($this->getSerializer()->normalize($view->getData()));
    }

    /**
     * @Route("/data")
     */
    public function data(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_'.$this->config->getName().'_LIST');

        $data = $this->config->getDatasource()->list();
        return new JsonResponse($data);
    }
}
