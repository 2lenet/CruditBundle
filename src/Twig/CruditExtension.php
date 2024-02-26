<?php

namespace Lle\CruditBundle\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Lle\CruditBundle\Contracts\ActionInterface;
use Lle\CruditBundle\Contracts\LayoutElementInterface;
use Lle\CruditBundle\Dto\Layout\LinkElement;
use Lle\CruditBundle\Registry\MenuRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class CruditExtension extends AbstractExtension
{
    public function __construct(
        private MenuRegistry $menuRegistry,
        private RouterInterface $router,
        private EntityManagerInterface $em,
        private ParameterBagInterface $parameterBag,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('crudit_menu_items', [$this, 'menuItems']),
            new TwigFunction('crudit_menu_active', [$this, 'menuIsActive']),
            new TwigFunction('crudit_hide_if_disabled', [$this, 'hideIfDisabled']),

        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('json_decode', [$this, 'jsonDecode']),
            new TwigFilter("get_crudit_routename", [$this, "getCruditRoutename"]),
            new TwigFilter('get_crudit_routename_key_object', [$this, 'getCruditRouteNameForKeyByObject']),
            new TwigFilter('get_crudit_routename_key_field', [$this, 'getCruditRouteNameForKeyByField']),
        ];
    }

    public function menuItems(string $navid): array
    {
        return $this->menuRegistry->getElements($navid);
    }

    public function menuIsActive(LayoutElementInterface $item, Request $request): bool
    {
        /** @var LinkElement $item */
        if (\count($item->getChildren()) > 0) {
            foreach ($item->getChildren() as $child) {
                if ($this->menuIsActive($child, $request)) {
                    return true;
                }
            }
        }

        if ($item->getPath() !== null && $item->getPath()->getRoute() !== 'lle_crudit_crud_index') {
            $currentRoute = $request->get('_route');
            $linkRoute = $item->getPath()->getRoute();
            $positionLastUnderscoreCurrentRoute = (strrpos($currentRoute, '_')) ?
                (int)strrpos($currentRoute, '_') :
                strlen($currentRoute);
            $positionLastUnderscoreLinkRoute = (strrpos($linkRoute, '_')) ?
                (int)strrpos($linkRoute, '_') :
                strlen($linkRoute);

            return
                (substr($currentRoute, 0, $positionLastUnderscoreCurrentRoute)) ===
                (substr($linkRoute, 0, $positionLastUnderscoreLinkRoute));
        } elseif ($item->getPath() !== null && $item->getPath()->getRoute() === 'lle_crudit_crud_index') {
            return $item->getPath()->getParams()['resource'] === $request->get('resource');
        }

        return false;
    }

    public function jsonDecode(string $value): mixed
    {
        return json_decode($value, true);
    }

    public function getCruditRoutename(object $value): ?string
    {
        $class = (new \ReflectionClass($value))->getShortName();

        $route = 'app_crudit_' . strtolower($class);

        try {
            $this->router->generate($route . '_index');
        } catch (RouteNotFoundException $e) {
            return null;
        }

        return $route . '_show';
    }

    public function getCruditRouteNameForKeyByObject(object $value, string $key, array $params = []): ?string
    {
        $class = (new \ReflectionClass($value))->getShortName();

        $route = 'app_crudit_' . strtolower($class);

        try {
            $this->router->generate($route . '_' . $key, $params);
        } catch (RouteNotFoundException $e) {
            return null;
        }

        return $route . '_' . $key;
    }

    public function getCruditRouteNameForKeyByField(object $item, string $field, string $key): ?string
    {
        $metadata = $this->em->getClassMetadata(get_class($item));

        $association = $metadata->getAssociationMapping($field);
        $targetEntity = $association['targetEntity'];

        $entityParts = explode('\\', $targetEntity);
        $entity = strtolower(end($entityParts));

        $route = 'app_crudit_' . $entity;

        try {
            $this->router->generate($route . '_' . $key);
        } catch (RouteNotFoundException $e) {
            return null;
        }

        return $route . '_' . $key;
    }

    public function hideIfDisabled(ActionInterface $action): bool
    {
        $hideIfDisabled = false;
        if ($action->getHideIfDisabled() !== null) {
            $hideIfDisabled = $action->getHideIfDisabled();
        } else {
            /** @var string $defaultValue */
            $defaultValue = $this->parameterBag->get('lle_crudit.hide_if_disabled');
            $hideIfDisabled = $defaultValue;
        }

        return $hideIfDisabled;
    }
}
