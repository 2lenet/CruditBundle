<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Controller;

use Lle\CruditBundle\Builder\BrickBuilder;
use Lle\CruditBundle\Provider\ConfiguratorProvider;
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
    /** @var ConfiguratorProvider */
    private $configuratorProvider;

    /** @var NormalizerInterface  */
    private $normalizer;

    /** @var BrickBuilder */
    private $brickBuilder;

    public function __construct(
        ConfiguratorProvider $configuratorProvider,
        BrickBuilder $brickBuilder,
        NormalizerInterface $normalizer
    ) {
        $this->configuratorProvider = $configuratorProvider;
        $this->brickBuilder = $brickBuilder;
        $this->normalizer = $normalizer;
    }

    /**
     * @Route("/{ressource}")
     */
    public function index(Request $request): Response
    {
        $configurator = $this->configuratorProvider->getConfiguratorByRequest($request);
        if ($configurator) {
            $views = $this->brickBuilder->build($configurator, $request);
            return $this->render('@LleCrudit/crud/index.html.twig', ['views' => $views]);
        }
        throw new NotFoundHttpException();
    }

    /**
     * @Route("/api/{ressource}")
     */
    public function apiIndex(Request $request): Response
    {
        $configurator = $this->configuratorProvider->getConfiguratorByRequest($request);
        if ($configurator) {
            $views = $this->brickBuilder->build($configurator, $request);
            return new JsonResponse(
                $this->normalizer->normalize($views)
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
