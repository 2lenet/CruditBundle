<?php

declare(strict_types=1);

namespace Lle\CruditBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

/**
 * Every CrudConfig of `lle_crudit.search.entities` is a service tagged `crudit.config`: a mistake shows at
 * `cache:clear`, not at the first search of a user in production.
 */
class SearchEntitiesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('lle_crudit.search.entities') || !$container->getParameter('lle_crudit.search.enabled')) {
            return;
        }

        $classes = [];
        foreach (array_keys($container->findTaggedServiceIds('crudit.config')) as $id) {
            $class = $container->getDefinition($id)->getClass() ?? $id;
            $classes[] = $container->getParameterBag()->resolveValue($class);
        }

        /** @var list<array{config: string}> $entities */
        $entities = $container->getParameter('lle_crudit.search.entities');
        foreach ($entities as $entity) {
            if (!\in_array($entity['config'], $classes, true)) {
                throw new InvalidArgumentException(sprintf(
                    'lle_crudit.search.entities: "%s" is not a CrudConfig of the application (a service tagged crudit.config).',
                    $entity['config'],
                ));
            }
        }
    }
}
