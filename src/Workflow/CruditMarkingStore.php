<?php

namespace Lle\CruditBundle\Workflow;


use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface;
use Symfony\Component\Workflow\MarkingStore\MethodMarkingStore;

class CruditMarkingStore implements MarkingStoreInterface
{
    private MethodMarkingStore $markingStore;

    private string $property;

    public function __construct(bool $singleState = false, string $property = "marking")
    {
        $this->property = $property;
        $this->markingStore = new MethodMarkingStore($singleState, $property);
    }

    public function getMarking(object $subject)
    {
        return $this->markingStore->getMarking($subject);
    }

    public function setMarking(object $subject, Marking $marking, array $context = [])
    {
        $this->markingStore->setMarking($subject, $marking, $context);
    }

    public function getProperty(): string
    {
        return $this->property;
    }
}
