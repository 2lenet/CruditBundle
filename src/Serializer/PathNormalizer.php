<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Serializer;

use Lle\CruditBundle\Dto\Path;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class PathNormalizer implements NormalizerInterface
{

    /** @var RouterInterface */
    private $router;

    /** @var ObjectNormalizer */
    private $normalizer;

    public function __construct(RouterInterface $router, ObjectNormalizer $normalizer)
    {
        $this->router = $router;
        $this->normalizer = $normalizer;
    }

    public function normalize($topic, string $format = null, array $context = [])
    {
        /** @var array $data */
        $data = $this->normalizer->normalize($topic, $format, $context);
        //$data['url'] = $this->router->generate($data['route'], $data['params']);
        return $data;
    }

    public function supportsNormalization($data, string $format = null, array $context = [])
    {
        return $data instanceof Path;
    }
}
