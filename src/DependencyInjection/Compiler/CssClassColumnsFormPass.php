<?php

declare(strict_types=1);

namespace Lle\CruditBundle\DependencyInjection\Compiler;

use Lle\CruditBundle\Exception\CruditException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * A CrudConfig of the 1.x that still overrides getCssClassColumnsForm() (Bootstrap classes) is refused when the
 * container is built (first page in dev, cache:clear of a deployment), all of them in one message, rather than when a
 * user opens one of its forms (lot 3.2.a).
 */
class CssClassColumnsFormPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $classes = [];
        foreach (array_keys($container->findTaggedServiceIds('crudit.config')) as $id) {
            $class = $container->getParameterBag()->resolveValue($container->getDefinition($id)->getClass() ?? $id);
            if (\is_string($class) && class_exists($class) && method_exists($class, 'getCssClassColumnsForm')) {
                $classes[] = $class;
            }
        }

        if ($classes !== []) {
            throw new CruditException(sprintf(
                'getCssClassColumnsForm() is removed in Crudit 2.0 (Bootstrap classes), still in %s: return the number'
                . ' of columns of the forms (1 to 4) from getFormColumns() instead, see UPGRADE-2.0.md.',
                implode(', ', $classes),
            ));
        }
    }
}
