<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Controller;

use Lle\CruditBundle\Provider\LayoutProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class UiController extends AbstractController
{
    /** @var KernelInterface @ */
    private $kernel;

    /** @var NormalizerInterface */
    private $normalizer;

    public function __construct(
        KernelInterface $kernel,
        NormalizerInterface $normalizer
    ) {
        $this->kernel = $kernel;
        $this->normalizer = $normalizer;
    }

    /**
     * @Route("/ui/layout", name="layout_config")
     */
    public function index(LayoutProvider $provider): Response
    {
        return new JsonResponse($this->normalizer->normalize($provider->getLayout()));
    }

    /**
     * @Route("/ui/crud/{resource}", name="crud_config")
     */
    public function crudConfig(string $resource): Response
    {
        $data = file_get_contents($this->kernel->getProjectDir() . '/data/crudit/crud/' . $resource . '.json');
        if ($data === false) {
            throw new \Exception('file layout not found');
        }
        return new JsonResponse(json_decode($data, true));
    }
}
