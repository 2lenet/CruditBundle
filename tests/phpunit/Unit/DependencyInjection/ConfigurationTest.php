<?php

declare(strict_types=1);

namespace phpunit\Unit\DependencyInjection;

use Lle\CruditBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

/**
 * lle_crudit.layout: nothing by default (no logo, no favicon, no link), a link of the footer has a route or an url
 * (route_params with a route only), logo_dark needs logo.
 */
class ConfigurationTest extends TestCase
{
    public function testLayoutDefaults(): void
    {
        self::assertSame([
            'logo' => null,
            'logo_dark' => null,
            'logo_small' => null,
            'favicon' => null,
            'version' => null,
            'release_date' => null,
            'footer_links' => [],
        ], $this->process([])['layout']);
    }

    public function testFooterLinks(): void
    {
        $layout = $this->process(['layout' => ['footer_links' => [
            ['label' => 'footer.accessibility', 'route' => 'app_accessibility'],
            ['label' => 'footer.legal', 'url' => 'https://example.org/legal'],
        ]]])['layout'];

        // assertEquals: the order of the keys is the one given by the project
        self::assertEquals([
            ['label' => 'footer.accessibility', 'route' => 'app_accessibility', 'route_params' => [], 'url' => null],
            ['label' => 'footer.legal', 'route' => null, 'route_params' => [], 'url' => 'https://example.org/legal'],
        ], $layout['footer_links']);
    }

    /**
     * @return iterable<string, array{array<string, string>}>
     */
    public static function invalidLinks(): iterable
    {
        yield 'route and url' => [['label' => 'footer.legal', 'route' => 'legal', 'url' => 'https://example.org']];
        yield 'neither' => [['label' => 'footer.legal']];
    }

    /**
     * @param array<string, string> $link
     */
    #[DataProvider('invalidLinks')]
    public function testFooterLinkHasARouteOrAnUrl(array $link): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('A link of the footer needs either a route or an url');

        $this->process(['layout' => ['footer_links' => [$link]]]);
    }

    public function testRouteParamsWithAnUrlAreRefused(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('A link of the footer with an url has no route_params');

        $this->process(['layout' => ['footer_links' => [
            ['label' => 'footer.legal', 'url' => 'https://example.org/legal', 'route_params' => ['id' => 1]],
        ]]]);
    }

    public function testLogoDarkNeedsLogo(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('lle_crudit.layout.logo_dark needs lle_crudit.layout.logo');

        $this->process(['layout' => ['logo_dark' => 'images/logo-dark.svg']]);
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    private function process(array $config): array
    {
        return (new Processor())->processConfiguration(new Configuration(), [$config]);
    }
}
