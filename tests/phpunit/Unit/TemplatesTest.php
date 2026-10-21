<?php

declare(strict_types=1);

namespace phpunit\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Every template of the bundle named in the PHP code or in another template exists (a template moved without its
 * references gives an error only on the page that renders it), and the folder `sb_admin` of 1.x is gone.
 */
class TemplatesTest extends TestCase
{
    private const string VIEWS = __DIR__ . '/../../../src/Resources/views';

    public function testReferencedTemplatesExist(): void
    {
        $missing = [];
        foreach (self::files(__DIR__ . '/../../../src') as $file) {
            if (!preg_match('/\.(php|twig)$/', $file)) {
                continue;
            }
            preg_match_all('#@LleCrudit/([A-Za-z0-9_/]+\.html\.twig)#', (string) file_get_contents($file), $matches);
            foreach ($matches[1] as $template) {
                if (!is_file(self::VIEWS . '/' . $template)) {
                    $missing[] = sprintf('%s: %s', substr($file, \strlen(__DIR__ . '/../../../')), $template);
                }
            }
        }

        self::assertSame([], array_values(array_unique($missing)));
    }

    public function testNoSbAdminFolder(): void
    {
        self::assertDirectoryDoesNotExist(self::VIEWS . '/layout/sb_admin');
    }

    /**
     * @return iterable<string>
     */
    private static function files(string $directory): iterable
    {
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->isFile() && !str_contains($file->getPathname(), '/Resources/public/')) {
                yield $file->getPathname();
            }
        }
    }
}
