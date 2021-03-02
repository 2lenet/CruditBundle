<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @Route("/data")
 */
class DataController extends AbstractController
{

    /** @var EntityManagerInterface  */
    private $em;

    /** @var SerializerInterface  */
    private $serializer;

    public function __construct(EntityManagerInterface $em, SerializerInterface $serializer)
    {
        $this->em = $em;
        $this->serializer = $serializer;
    }

    /**
     * @Route("/list/{ressource}")
     */
    public function index(): Response
    {
        return new Response();
    }
}
