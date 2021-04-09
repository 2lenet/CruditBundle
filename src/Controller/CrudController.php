<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Controller;

use Lle\CruditBundle\Brick\BrickResponseCollector;
use Lle\CruditBundle\Builder\BrickBuilder;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Provider\ConfigProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @Route("/crud")
 */
class CrudController extends AbstractController
{
    /** @var ConfigProvider */
    private $configProvider;

    /** @var NormalizerInterface  */
    private $normalizer;

    /** @var BrickBuilder */
    private $brickBuilder;

    /** @var BrickResponseCollector */
    private $brickResponseCollector;

    public function __construct(
        ConfigProvider $configProvider,
        BrickBuilder $brickBuilder,
        NormalizerInterface $normalizer,
        BrickResponseCollector $brickResponseCollector
    ) {
        $this->configProvider = $configProvider;
        $this->brickBuilder = $brickBuilder;
        $this->normalizer = $normalizer;
        $this->brickResponseCollector = $brickResponseCollector;
    }

    /**
     * @Route("/{resource}")
     */
    public function index(Request $request): Response
    {
        $configurator = $this->configProvider->getConfiguratorByRequest($request);
        if ($configurator) {
            $views = $this->brickBuilder->build($configurator, CrudConfigInterface::INDEX, $request);
            $response = $this->render('@LleCrudit/crud/index.html.twig', ['views' => $views]);
            return $this->brickResponseCollector->handle($request, $response);
        }
        throw new NotFoundHttpException();
    }

    /**
     * @Route("/{resource}/show/{id}")
     */
    public function show(Request $request): Response
    {
        $configurator = $this->configProvider->getConfiguratorByRequest($request);
        if ($configurator) {
            $views = $this->brickBuilder->build($configurator, CrudConfigInterface::SHOW, $request);
            $response = $this->render('@LleCrudit/crud/index.html.twig', ['views' => $views]);
            return $this->brickResponseCollector->handle($request, $response);
        }
        throw new NotFoundHttpException();
    }

    /**
     * @Route("/{resource}/new")
     */
    public function new(Request $request): Response
    {
        $configurator = $this->configProvider->getConfiguratorByRequest($request);
        if ($configurator) {
            $views = $this->brickBuilder->build($configurator, CrudConfigInterface::NEW, $request);
            $response = $this->render('@LleCrudit/crud/index.html.twig', ['views' => $views]);
            return $this->brickResponseCollector->handle($request, $response);
        }
        throw new NotFoundHttpException();
    }

    /**
     * @Route("/{resource}/edit/{id}")
     */
    public function edit(Request $request): Response
    {
        $configurator = $this->configProvider->getConfiguratorByRequest($request);
        if ($configurator) {
            $views = $this->brickBuilder->build($configurator, CrudConfigInterface::EDIT, $request);
            $response = $this->render('@LleCrudit/crud/index.html.twig', ['views' => $views]);
            return $this->brickResponseCollector->handle($request, $response);
        }
        throw new NotFoundHttpException();
    }

    /**
     * @Route("/api/{resource}")
     */
    public function apiIndex(Request $request): Response
    {
        $configurator = $this->configProvider->getConfiguratorByRequest($request);
        if ($configurator) {
            $views = $this->brickBuilder->build($configurator, CrudConfigInterface::INDEX, $request);
            return new JsonResponse(
                $this->normalizer->normalize($views)
            );
        }
        throw new NotFoundHttpException();
    }

    /**
     * @Route("/api/config/{resource}/{id}")
     */
    public function apiConfig(string $id): Response
    {
        $view = $this->brickBuilder->getView($id);
        if ($view) {
            return new JsonResponse(
                $view->getConfig()
            );
        }
        throw new NotFoundHttpException();
    }

    /**
     * @Route("/api/data/{resource}/{id}")
     */
    public function apiData(string $id): Response
    {
        $view = $this->brickBuilder->getView($id);
        if ($view) {
            return new JsonResponse(
                $view->getData()
            );
        }
        throw new NotFoundHttpException();
    }
}
