<?php
namespace Lle\CruditBundle\Serializer;

use Lle\CruditBundle\Layout\LayoutInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class LayoutNormalizer implements NormalizerInterface
{
    private $normalizer;

    public function __construct(ObjectNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    public function normalize($topic, string $format = null, array $context = [])
    {
        $data = $this->normalizer->normalize($topic, $format, $context);

        return $data;
    }

    public function supportsNormalization($data, string $format = null, array $context = [])
    {
        return $data instanceof LayoutInterface;
    }
}
