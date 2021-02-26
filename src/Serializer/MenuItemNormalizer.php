<?php
namespace Lle\CruditBundle\Serializer;

use Lle\CruditBundle\Dto\MenuItem;
use Lle\CruditBundle\Dto\Path;
use Lle\CruditBundle\Layout\LayoutInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class MenuItemNormalizer implements NormalizerInterface
{
    private $router;
    private $normalizer;

    public function __construct(ObjectNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    public function normalize($topic, string $format = null, array $context = [])
    {
        $data = $this->normalizer->normalize($topic, $format, $context);
        $daat['bibi'] = 'bb';
        return $data;
    }

    public function supportsNormalization($data, string $format = null, array $context = [])
    {
        return $data instanceof MenuItem;
    }
}
