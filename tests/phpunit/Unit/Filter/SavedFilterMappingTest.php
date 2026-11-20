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
