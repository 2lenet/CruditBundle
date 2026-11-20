<?php

declare(strict_types=1);

namespace phpunit\Unit\Filter;

use Lle\CruditBundle\DependencyInjection\LleCruditExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * The entity of the saved filters is mapped in the Doctrine of the project, unless the feature is turned off.
 */
class SavedFilterMappingTest extends TestCase
{
    /**
     * @param list<array<string, mixed>> $configs
     */
    #[DataProvider('cases')]
    public function testTheEntityIsMappedUnlessTurnedOff(array $configs, bool $doctrine, bool $mapped): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.bundles', $doctrine ? ['DoctrineBundle' => 'x'] : []);
        $container->setParameter('kernel.bundles_metadata', []);
        foreach ($configs as $config) {
            $container->prependExtensionConfig('lle_crudit', $config);
        }

        (new LleCruditExtension())->prepend($container);

        $mappings = $container->getExtensionConfig('doctrine')[0]['orm']['mappings'] ?? [];
        self::assertSame($mapped, isset($mappings['LleCruditBundle']));
        if ($mapped) {
            self::assertSame('Lle\\CruditBundle\\Entity', $mappings['LleCruditBundle']['prefix']);
            self::assertSame('attribute', $mappings['LleCruditBundle']['type']);
            self::assertDirectoryExists($mappings['LleCruditBundle']['dir']);
        }
    }

    /**
     * With `entity_managers`, the mapping goes to the manager the project chose: a mapping at the root of `orm` would
     * make a manager `default` of its own with this entity only.
     *
     * @param list<array<string, mixed>> $lle
     * @param array<string, mixed> $doctrine
     */
    #[DataProvider('managers')]
    public function testTheMappingGoesToTheManagerOfTheProject(array $lle, array $doctrine, ?string $manager): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.bundles', ['DoctrineBundle' => 'x']);
        $container->setParameter('kernel.bundles_metadata', []);
        foreach ($lle as $config) {
            $container->prependExtensionConfig('lle_crudit', $config);
        }
        $container->prependExtensionConfig('doctrine', $doctrine);

        (new LleCruditExtension())->prepend($container);

        $orm = $container->getExtensionConfig('doctrine')[0]['orm'];
        if ($manager === null) {
            self::assertArrayHasKey('mappings', $orm);
            self::assertArrayNotHasKey('entity_managers', $orm);

            return;
        }
        self::assertArrayNotHasKey('mappings', $orm);
        self::assertSame([$manager], array_keys($orm['entity_managers']));
        self::assertArrayHasKey('LleCruditBundle', $orm['entity_managers'][$manager]['mappings']);
    }

    /**
     * @return iterable<string, array{list<array<string, mixed>>, array<string, mixed>, ?string}>
     */
    public static function managers(): iterable
    {
        $two = ['orm' => ['default_entity_manager' => 'main', 'entity_managers' => ['main' => [], 'other' => []]]];
        yield 'one manager, no entity_managers' => [[], ['orm' => ['auto_mapping' => true]], null];
        yield 'the default one of the project' => [[], $two, 'main'];
        yield 'the first, without a default' => [[], ['orm' => ['entity_managers' => ['main' => [], 'other' => []]]], 'main'];
        yield 'one called default' => [[], ['orm' => ['entity_managers' => ['other' => [], 'default' => []]]], 'default'];
        yield 'the one the bundle is told' => [[['saved_filters' => ['entity_manager' => 'other']]], $two, 'other'];
    }

    /**
     * @return iterable<string, array{list<array<string, mixed>>, bool, bool}>
     */
    public static function cases(): iterable
    {
        yield 'by default' => [[], true, true];
        yield 'turned off' => [[['saved_filters' => ['enabled' => false]]], true, false];
        yield 'turned off, another file does not say' => [[['saved_filters' => ['enabled' => false]], ['saved_filters' => ['max' => 3]]], true, false];
        yield 'turned on explicitly' => [[['saved_filters' => ['enabled' => true]]], true, true];
        yield 'without Doctrine' => [[], false, false];
    }
}
