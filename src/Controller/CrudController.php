<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Controller;

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

    public function __construct(
        ConfigProvider $configProvider,
        BrickBuilder $brickBuilder,
        NormalizerInterface $normalizer
    ) {
        $this->configProvider = $configProvider;
        $this->brickBuilder = $brickBuilder;
        $this->normalizer = $normalizer;
    }

    /**
     * @Route("/{ressource}")
     */
    public function index(Request $request): Response
    {
        $configurator = $this->configProvider->getConfiguratorByRequest($request);
        if ($configurator) {
            $views = $this->brickBuilder->build($configurator, CrudConfigInterface::INDEX, $request);
            return $this->render('@LleCrudit/crud/index.html.twig', ['views' => $views]);
        }
        throw new NotFoundHttpException();
    }

    /**
     * @Route("/api/{ressource}")
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
     * @Route("/api/config/{ressource}/{id}")
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
     * @Route("/api/data/{ressource}/{id}")
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

    /**
     * @Route("/{ressource}/{id}")
     */
    public function show(string $ressource): Response
    {
        return new Response($ressource . 'show');
    }
}
