<?php

namespace Lle\CruditBundle\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class RouteFilteredLinkExtension extends AbstractExtension
{
    private RouterInterface $router;

    private EntityManagerInterface $em;

    public function __construct(RouterInterface $router, EntityManagerInterface $em)
    {
        $this->router = $router;
        $this->em = $em;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('crudit_route_filtered_link', [$this, 'getRouteFilteredLink']),
            new TwigFunction('entity_id_to_tomselect', [$this, 'getEntityIdToTomselect'])
        ];
    }

    public function getRouteFilteredLink(string $entity, array $filters): string
    {
        $route = 'app_crudit_' . $entity . '_index';

        $parameters = [];
        foreach ($filters as $field => $filter) {
            $filterName = 'filter_' . $entity . '_' . $field;

            if (!array_key_exists('op', $filter)) {
                $parameters[$filterName . '_op'] = 'eq';
            }

            foreach ($filter as $key => $value) {
                $parameters[$filterName . '_' . $key] = $value;
            }
        }

        return $this->router->generate($route, $parameters, UrlGeneratorInterface::ABSOLUTE_PATH);
    }

    public function getEntityIdToTomselect(int $id, string $class): array
    {
        $item = $this->em->find($class, $id);

        return [
            'id' => $id,
            'text' => (string)$item
        ];
    }
}
