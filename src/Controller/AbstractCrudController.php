<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Controller;

use App\Crudit\CrudConfig\EspeceCrudConfig;
use Lle\CruditBundle\Builder\BrickBuilder;
use Lle\CruditBundle\Contracts\PageConfigInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @Route("/espece")
 */
abstract class AbstractCrudController extends AbstractController
{
    /** @var PageConfigInterface  */
    protected $config;



    public static function getSubscribedServices(){
        $subscribeds = parent::getSubscribedServices();
        $subscribeds[BrickBuilder::class] = '?'.BrickBuilder::class;
        $subscribeds[SerializerInterface::class] = '?'. SerializerInterface::class;
        return $subscribeds;
    }

    public function getBrickBuilder(): BrickBuilder
    {
        return $this->get(BrickBuilder::class);
    }

    public function normalize($object): array
    {
        $this->get(SerializerInterface::class)->normalize($object);
    }
}
