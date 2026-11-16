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
 * lle_crudit.search: on by default, with the screens and no entity; an entity is a CrudConfig class, alone or with its
 * options.
 * hide_if_disabled: true by default, delete_hide_if_disabled: as hide_if_disabled unless set.
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

    /**
     * Removed in 2.0 (the banner of the impersonation is always shown): a project that keeps it is told at once.
     */
    public function testAddExitImpersonationButtonIsRemoved(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Unrecognized option "add_exit_impersonation_button"');

        $this->process(['add_exit_impersonation_button' => true]);
    }

    public function testLogoDarkNeedsLogo(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('lle_crudit.layout.logo_dark needs lle_crudit.layout.logo');

        $this->process(['layout' => ['logo_dark' => 'images/logo-dark.svg']]);
    }

    public function testSearchDefaults(): void
    {
        self::assertSame([
            'enabled' => true,
            'min_length' => 2,
            'limit' => 10,
            'screens' => true,
            'entities' => [],
        ], $this->process([])['search']);
    }

    public function testSearchEntities(): void
    {
        $entities = $this->process(['search' => ['entities' => [
            'App\\Crudit\\Config\\ClientCrudConfig',
            ['config' => 'App\\Crudit\\Config\\InvoiceCrudConfig', 'title' => 'menu.invoices', 'limit' => 3, 'subtitle' => 'customer.name'],
        ]]])['search']['entities'];

        self::assertSame([
            ['config' => 'App\\Crudit\\Config\\ClientCrudConfig', 'title' => null, 'limit' => null, 'subtitle' => null],
            ['config' => 'App\\Crudit\\Config\\InvoiceCrudConfig', 'title' => 'menu.invoices', 'limit' => 3, 'subtitle' => 'customer.name'],
        ], $entities);
    }

    public function testSearchEntityNeedsItsConfig(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->process(['search' => ['entities' => [['title' => 'menu.clients']]]]);
    }

    public function testSearchLimitsAreBounded(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->process(['search' => ['limit' => 21]]);
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    /**
     * The actions whose right is refused are hidden by default (2.0); a deletion follows hide_if_disabled unless set.
     */
    public function testHideIfDisabledDefaults(): void
    {
        $config = $this->process([]);

        self::assertTrue($config['hide_if_disabled']);
        self::assertNull($config['delete_hide_if_disabled']);
        self::assertFalse($this->process(['delete_hide_if_disabled' => false])['delete_hide_if_disabled']);
    }

    /**
     * The record (lot 3.3.a): 4 fields per line by default (1 to 4); the classes of Bootstrap and the number of
     * sections per line are refused.
     */
    public function testTheRecord(): void
    {
        self::assertSame(4, $this->process([])['show_columns']);
        self::assertSame(2, $this->process(['show_columns' => 2])['show_columns']);
    }

    public function testTheFieldsOfARecordAreAtMostFourPerLine(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->process(['show_columns' => 5]);
    }

    /**
     * @return iterable<string, array{string, string|int}>
     */
    public static function removedKeysOfTheRecord(): iterable
    {
        yield 'css_class_columns_show' => ['css_class_columns_show', 'col-md-6'];
        yield 'css_class_columns_card' => ['css_class_columns_card', 'col-md-6'];
        yield 'number_cards_per_row' => ['number_cards_per_row', 2];
    }

    #[DataProvider('removedKeysOfTheRecord')]
    public function testTheRemovedKeysOfTheRecordAreRefused(string $key, string|int $value): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Unrecognized option "' . $key . '"');

        $this->process([$key => $value]);
    }

    private function process(array $config): array
    {
        return (new Processor())->processConfiguration(new Configuration(), [$config]);
    }
}
