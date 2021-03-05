<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Serializer;

use Lle\CruditBundle\Layout\LayoutInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class LayoutNormalizer implements NormalizerInterface
{
    /** @var ObjectNormalizer  */
    private $normalizer;

    public function __construct(ObjectNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    public function normalize($topic, string $format = null, array $context = [])
    {
        /** @var LayoutInterface $topic */
        /** @var array $data */
        $data = $this->normalizer->normalize($topic, $format, $context);
        $data['elements'] = [];
        foreach ($topic->getElementNames() as $name) {
            $data['elements'][$name] = [];
            foreach ($topic->getElements($name) as $element) {
                $data['elements'][$name][] = $this->normalizer->normalize($element, $format, $context);
            }
        }
        return $data;
    }

    public function supportsNormalization($data, string $format = null, array $context = [])
    {
        return $data instanceof LayoutInterface;
    }
}
