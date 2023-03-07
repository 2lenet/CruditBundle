<?php

namespace Lle\CruditBundle\Twig;

use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
            new TwigFunction('crudit_route_filtered_link', [$this, 'getRouteFilteredLink'])
        ];
    }

    public function getRouteFilteredLink(string $entity, array $filters)
    {
        $route = 'app_crudit_' . $entity . '_index';

        try {
            $this->router->generate($route);
        } catch (RouteNotFoundException $e) {
            return null;
        }

        $parameters = [];
        foreach ($filters as $filter) {
            $filterName = 'filter_' . $entity . '_' . $filter[0];

            if (isset($filter[2])) {
                $parameters[$filterName . '_op'] = $filter[2];
            } else {
                $parameters[$filterName . '_op'] = 'eq';
            }

            if (is_array($filter[1])) {
                foreach ($filter[1] as $key => $value) {
                    $parameters[$filterName . '_' . $key] = $value;
                }
            } else {
                $parameters[$filterName . '_value'] = $filter[1];
            }
        }

        return $this->router->generate($route, $parameters, UrlGeneratorInterface::ABSOLUTE_PATH);
    }
}
