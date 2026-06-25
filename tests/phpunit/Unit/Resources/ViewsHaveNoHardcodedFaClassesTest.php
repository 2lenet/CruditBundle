<?php

declare(strict_types=1);

namespace phpunit\Unit\Resources;

use PHPUnit\Framework\TestCase;

/**
 * Guards against regressions: no hard-coded Font Awesome icon class
 * should appear in bundle templates. All icons must go through the
 * `crudit_icon()` Twig function so that lle_crudit.icons overrides
 * (e.g. Bootstrap Icons) actually take effect.
 *
 * Allowed: pure size/width modifiers (`fa-sm`, `fa-fw`, `fa-lg`, `fa-xs`)
 * that don't designate an icon glyph.
 */
class ViewsHaveNoHardcodedFaClassesTest extends TestCase
{
    private const VIEWS_DIR = __DIR__ . '/../../../../src/Resources/views';

    /** Size/width modifiers that aren't glyphs — these may stay. */
    private const ALLOWED_MODIFIERS = ['fa-sm', 'fa-fw', 'fa-lg', 'fa-xs'];

    public function testNoHardcodedFontAwesomeClassesInViews(): void
    {
        $viewsDir = realpath(self::VIEWS_DIR);
        self::assertNotFalse($viewsDir, 'Views directory not found.');

        $offenders = [];
        /** @var \SplFileInfo $file */
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($viewsDir)) as $file) {
            if (!$file->isFile() || !str_ends_with($file->getFilename(), '.twig')) {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            self::assertNotFalse($contents);

            foreach ($this->findOffendingLines($contents) as $lineNumber => $line) {
                $offenders[] = sprintf(
                    "%s:%d → %s",
                    str_replace($viewsDir . '/', '', $file->getPathname()),
                    $lineNumber,
                    trim($line),
                );
            }
        }

        self::assertSame(
            [],
            $offenders,
            "Hard-coded FA classes found in views — migrate them to crudit_icon('key'):\n  " .
            implode("\n  ", $offenders),
        );
    }

    /**
     * @return iterable<int, string>
     */
    private function findOffendingLines(string $contents): iterable
    {
        $lines = preg_split("/\r?\n/", $contents);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $i => $line) {
            // Matches "fa fa-…", "fas fa-…", "far fa-…", "fal fa-…", "fab fa-…"
            // (icon-glyph references), but tolerates lines that only carry
            // modifier classes like "fa-sm" / "fa-fw".
            if (preg_match('/\bfa[srlb]?\s+fa-[a-z0-9-]+/i', $line)) {
                yield $i + 1 => $line;

                continue;
            }

            // Catch <i class="… fa-something …"> where fa-something isn't a modifier
            // and there's no crudit_icon() in the same class attribute.
            if (preg_match_all('/class="([^"]*\bfa-[a-z0-9-]+[^"]*)"/i', $line, $matches)) {
                foreach ($matches[1] as $classAttr) {
                    $faTokens = [];
                    preg_match_all('/\bfa-[a-z0-9-]+/i', $classAttr, $tokenMatches);
                    foreach ($tokenMatches[0] as $token) {
                        if (!in_array($token, self::ALLOWED_MODIFIERS, true)) {
                            $faTokens[] = $token;
                        }
                    }

                    if ($faTokens !== []) {
                        yield $i + 1 => $line;
                        break;
                    }
                }
            }
        }
    }
}
