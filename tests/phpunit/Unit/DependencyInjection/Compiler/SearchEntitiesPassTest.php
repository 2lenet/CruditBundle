<?php

declare(strict_types=1);

namespace phpunit\Unit\DependencyInjection\Compiler;

use Lle\CruditBundle\DependencyInjection\Compiler\SearchEntitiesPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

/**
 * The CrudConfigs of lle_crudit.search.entities are checked when the container is built.
 */
class SearchEntitiesPassTest extends TestCase
{
    public function testKnownCrudConfig(): void
    {
        $container = $this->container(['App\\CustomerCrudConfig']);

        (new SearchEntitiesPass())->process($container);

        $this->addToAssertionCount(1);
    }

    public function testUnknownCrudConfigIsRefused(): void
    {
        $container = $this->container(['App\\GhostCrudConfig']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('lle_crudit.search.entities: "App\GhostCrudConfig" is not a CrudConfig');
        (new SearchEntitiesPass())->process($container);
    }

    public function testNothingIsCheckedWhenTheSearchIsDisabled(): void
    {
        $container = $this->container(['App\\GhostCrudConfig'], enabled: false);

        (new SearchEntitiesPass())->process($container);

        $this->addToAssertionCount(1);
    }

    /**
     * @param list<string> $configs
     */
    private function container(array $configs, bool $enabled = true): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('lle_crudit.search.enabled', $enabled);
        $container->setParameter('lle_crudit.search.entities', array_map(static fn (string $config): array => ['config' => $config], $configs));
        $container->setDefinition('App\\CustomerCrudConfig', (new Definition('App\\CustomerCrudConfig'))->addTag('crudit.config'));

        return $container;
    }
}
