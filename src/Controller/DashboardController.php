<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{

    /** @var KernelInterface */
    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @Route("/")
     */
    public function index(): Response
    {
        return $this->render('@LleCrudit/dashboard/dashboard.html.twig');
    }
}
