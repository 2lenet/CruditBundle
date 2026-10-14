<?php

declare(strict_types=1);

namespace phpunit\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * The catalogues of the bundle (fr, en, de, it) have the same keys, no empty text, and the same parameters.
 */
class TranslationsTest extends TestCase
{
    private const string DOMAIN = 'LleCruditBundle';

    private const string REFERENCE = 'fr';

    private const array LOCALES = ['fr', 'en', 'de', 'it'];

    public function testEachLocaleHasACatalogue(): void
    {
        $files = glob(\dirname(__DIR__, 3) . '/src/Resources/translations/' . self::DOMAIN . '.*.yaml');
        self::assertIsArray($files);
        $locales = array_map(static fn (string $file): string => explode('.', basename($file))[1], $files);
        sort($locales);
        $expected = self::LOCALES;
        sort($expected);

        self::assertSame($expected, $locales);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function locales(): iterable
    {
        foreach (self::LOCALES as $locale) {
            yield $locale => [$locale];
        }
    }

    #[DataProvider('locales')]
    public function testSameKeysAsTheReference(string $locale): void
    {
        self::assertSame(array_keys(self::messages(self::REFERENCE)), array_keys(self::messages($locale)));
    }

    #[DataProvider('locales')]
    public function testNoEmptyText(string $locale): void
    {
        foreach (self::messages($locale) as $key => $text) {
            self::assertNotSame('', trim($text), \sprintf('"%s" is empty in %s', $key, $locale));
        }
    }

    #[DataProvider('locales')]
    public function testSameParameters(string $locale): void
    {
        $reference = self::messages(self::REFERENCE);
        foreach (self::messages($locale) as $key => $text) {
            self::assertSame(self::parameters($reference[$key] ?? ''), self::parameters($text), \sprintf('Parameters of "%s" in %s', $key, $locale));
        }
    }

    /**
     * @return array<string, string> texts keyed by flattened key (`crudit.action.edit`), sorted by key
     */
    private static function messages(string $locale): array
    {
        $messages = self::flatten(Yaml::parseFile(\dirname(__DIR__, 3) . '/src/Resources/translations/' . self::DOMAIN . '.' . $locale . '.yaml'));
        ksort($messages);

        return $messages;
    }

    /**
     * @return array<string, string>
     */
    private static function flatten(mixed $messages, string $prefix = ''): array
    {
        if (!\is_array($messages)) {
            return [$prefix => \is_scalar($messages) ? (string) $messages : ''];
        }

        $flat = [];
        foreach ($messages as $key => $message) {
            $flat += self::flatten($message, '' === $prefix ? (string) $key : $prefix . '.' . $key);
        }

        return $flat;
    }

    /**
     * @return list<string>
     */
    private static function parameters(string $text): array
    {
        preg_match_all('/%[a-z_]+%/', $text, $matches);
        $parameters = array_values(array_unique($matches[0]));
        sort($parameters);

        return $parameters;
    }
}
