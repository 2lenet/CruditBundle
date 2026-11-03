<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Twig;

use Twig\Attribute\AsTwigTest;

/**
 * `value is crudit_empty`: a value of a field not filled in (null, '', an empty array or collection), shown as a dash
 * read as "Not set" by the templates of the fields (field/_empty.html.twig). False and 0 are values.
 */
class FieldExtension
{
    #[AsTwigTest('crudit_empty')]
    public static function isEmpty(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (is_array($value)) {
            return $value === [];
        }
        // A Countable only: counting another Traversable (a generator) would consume it before it is shown
        return $value instanceof \Countable && \count($value) === 0;
    }
}
