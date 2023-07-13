<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Lle\CruditBundle\Brick\BrickResponseCollector;
use Lle\CruditBundle\Builder\BrickBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\SerializerInterface;

abstract class AbstractCrudController extends AbstractController
{
    public static function getSubscribedServices(): array
    {
        $subscribeds = parent::getSubscribedServices();
        $subscribeds[BrickBuilder::class] = '?' . BrickBuilder::class;
        $subscribeds[SerializerInterface::class] = '?' . SerializerInterface::class;
        $subscribeds[BrickResponseCollector::class] = '?' . BrickResponseCollector::class;
        $subscribeds['doctrine'] = '?' . ManagerRegistry::class;

        return $subscribeds;
    }

    protected function getBrickBuilder(): object
    {
        return $this->container->get(BrickBuilder::class);
    }

    protected function getSerializer(): object
    {
        return $this->container->get(SerializerInterface::class);
    }

    protected function getBrickResponseCollector(): object
    {
        return $this->container->get(BrickResponseCollector::class);
    }

    protected function getDoctrine(): ManagerRegistry
    {
        return $this->container->get('doctrine');
    }
}
