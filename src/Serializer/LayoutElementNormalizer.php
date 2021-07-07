<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Serializer;

use Lle\CruditBundle\Contracts\LayoutElementInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class LayoutElementNormalizer implements NormalizerInterface
{
    /** @var ObjectNormalizer $normalizer */
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
        return $data instanceof LayoutElementInterface;
    }
}
