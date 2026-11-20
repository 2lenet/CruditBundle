<?php

namespace Lle\CruditBundle\Twig;

use Lle\CruditBundle\Contracts\FilterTypeInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class RouteFilteredLinkExtension extends AbstractExtension
{
    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('crudit_route_filtered_link', [$this, 'getRouteFilteredLink']),
        ];
    }

    public function getRouteFilteredLink(string $entity, array $filters): string
    {
        $route = 'app_crudit_' . $entity . '_index';

        $parameters = [];
        foreach ($filters as $field => $filter) {
            $filterName = 'filter_' . $entity . '_' . $field;

            if (!array_key_exists('op', $filter)) {
                $parameters[$filterName . '_op'] = FilterTypeInterface::OPERATOR_EQUAL;
            }

            foreach ($filter as $key => $value) {
                $parameters[$filterName . '_' . $key] = $value;
            }
        }

        $parameters[$entity . '_offset'] = 0;

        return $this->router->generate($route, $parameters, UrlGeneratorInterface::ABSOLUTE_PATH);
    }
}
