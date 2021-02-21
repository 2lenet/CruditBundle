<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class UiController extends AbstractController
{
    /** @var KernelInterface @ */
    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @Route("/ui/layout", name="layout_config")
     */
    public function index(): Response
    {
        $data = file_get_contents($this->kernel->getProjectDir() . '/data/crudit/layout.json');
        if ($data === false) {
            throw new \Exception('file layout not found');
        }
        return new JsonResponse(json_decode($data, true));
    }

    /**
     * @Route("/ui/crud/{resource}", name="crud_config")
     */
    public function crudConfig($resource): Response
    {
        $data = file_get_contents($this->kernel->getProjectDir().'/data/crudit/crud/'.$resource.'.json');
        if ($data === false) {
            throw new \Exception('file layout not found');
        }
        return new JsonResponse(json_decode($data, true));
    }
}
