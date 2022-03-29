<?php

namespace Lle\CruditBundle\DataCollector;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector as BaseDataCollector;

/**
 * Collects information about the requests related to Crudit and displays
 */
class DataCollector extends BaseDataCollector
{
    /** @var EntityManagerInterface */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function reset()
    {
        $this->data = [];
    }

    public function collect(Request $request, Response $response, $exception = null)
    {
        $entities = [];
        $metas = $this->em->getMetadataFactory()->getAllMetadata();
        foreach ($metas as $meta) {
            $eData = [
                'name' => $meta->getName(),
            ];
            $rc = $meta->getReflectionClass();
            $eData['has_tostring'] = $rc->hasMethod('__toString');
            $eData['has_candelete'] = $rc->hasMethod('candelete');
            $entities[] = $eData;
        }

        $this->data = $entities;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getName()
    {
        return 'crudit';
    }
}
