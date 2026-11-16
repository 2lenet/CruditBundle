<?php

declare(strict_types=1);

namespace Lle\CruditBundle\DependencyInjection\Compiler;

use Lle\CruditBundle\Exception\CruditException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * A CrudConfig of the 1.x that still overrides a method of the record removed in 2.0 (lot 3.3.a) is refused when the
 * container is built (first page in dev, cache:clear of a deployment), all of them in one message with what to do
 * instead, rather than ignored:
 * - getCssClassColumnsShow(), getCssClassColumnsCard(): Bootstrap classes;
 * - getNumberCardsPerRow(): the sections of a record are in 2 columns on a wide screen as soon as there are 2.
 */
class RemovedRecordMethodsPass implements CompilerPassInterface
{
    /** What to do instead, by the removed methods */
    private const REMOVED = [
        'return the number of fields per line from getShowColumns() or getShowSectionColumns(), and widen a field'
        . ' with its option span' => ['getCssClassColumnsShow', 'getCssClassColumnsCard'],
        'the sections of a record are in 2 columns on a wide screen as soon as there are 2, a section takes the'
        . ' whole line with getShowWideSections()' => ['getNumberCardsPerRow'],
    ];

    public function process(ContainerBuilder $container): void
    {
        $classes = [];
        foreach (array_keys($container->findTaggedServiceIds('crudit.config')) as $id) {
            $class = $container->getParameterBag()->resolveValue($container->getDefinition($id)->getClass() ?? $id);
            if (\is_string($class) && class_exists($class)) {
                $classes[] = $class;
            }
        }

        $errors = [];
        foreach (self::REMOVED as $instead => $methods) {
            $found = [];
            foreach ($classes as $class) {
                foreach ($methods as $method) {
                    if (method_exists($class, $method)) {
                        $found[] = $class . '::' . $method . '()';
                    }
                }
            }
            if ($found !== []) {
                $errors[] = implode(', ', $found) . ': ' . $instead;
            }
        }

        if ($errors !== []) {
            throw new CruditException(sprintf(
                'Methods of the record removed in Crudit 2.0, still in %s; see UPGRADE-2.0.md.',
                implode('; ', $errors),
            ));
        }
    }
}
