<?php

declare(strict_types=1);

namespace phpunit\Unit\Twig;

use Lle\CruditBundle\Twig\LayoutExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * The version falls back on the Twig global app_version; an invalid release date is logged and not shown.
 */
class LayoutExtensionTest extends TestCase
{
    /**
     * @return iterable<string, array{?string, mixed, ?string}>
     */
    public static function versions(): iterable
    {
        yield 'configuration' => ['2.3.1', '1.0', '2.3.1'];
        yield 'global app_version' => [null, '1.0', '1.0'];
        yield 'empty configuration falls back' => ['', '1.0', '1.0'];
        yield 'nothing' => [null, null, null];
        yield 'empty global' => [null, '', null];
    }

    #[DataProvider('versions')]
    public function testVersion(?string $configured, mixed $global, ?string $expected): void
    {
        $twig = new Environment(new ArrayLoader());
        if ($global !== null) {
            $twig->addGlobal('app_version', $global);
        }

        self::assertSame($expected, (new LayoutExtension($this->config(version: $configured)))->layout($twig)['version']);
    }

    public function testReleaseDate(): void
    {
        $date = (new LayoutExtension($this->config(releaseDate: '2026-10-21')))->layout(new Environment(new ArrayLoader()))['release_date'];

        self::assertInstanceOf(\DateTimeImmutable::class, $date);
        self::assertSame('2026-10-21 00:00:00', $date->format('Y-m-d H:i:s'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidDates(): iterable
    {
        yield 'french format' => ['21/10/2026'];
        yield 'not a day' => ['2026-02-30'];
        yield 'text' => ['tomorrow'];
    }

    #[DataProvider('invalidDates')]
    public function testInvalidReleaseDateIsLoggedAndNotShown(string $value): void
    {
        $logger = new class () extends AbstractLogger {
            /** @var list<string> */
            public array $levels = [];

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->levels[] = (string) $level;
            }
        };

        $layout = (new LayoutExtension($this->config(releaseDate: $value), $logger))->layout(new Environment(new ArrayLoader()));

        self::assertNull($layout['release_date']);
        self::assertSame(['warning'], $logger->levels);
    }

    /**
     * @return array{
     *     logo: ?string,
     *     logo_dark: ?string,
     *     logo_small: ?string,
     *     favicon: ?string,
     *     version: ?string,
     *     release_date: ?string,
     *     footer_links: list<array{label: string, route: ?string, route_params: array<string, mixed>, url: ?string}>,
     * }
     */
    private function config(?string $version = null, ?string $releaseDate = null): array
    {
        return [
            'logo' => null,
            'logo_dark' => null,
            'logo_small' => null,
            'favicon' => null,
            'version' => $version,
            'release_date' => $releaseDate,
            'footer_links' => [],
        ];
    }
}
