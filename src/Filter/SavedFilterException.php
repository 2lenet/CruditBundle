<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Filter;

/**
 * A saved set of filters that cannot be saved or recalled; `reason` is the end of the translation key of the message
 * (`crudit.saved_filters.error.<reason>`).
 */
class SavedFilterException extends \RuntimeException
{
    public const string UNAVAILABLE = 'unavailable';
    public const string NO_FILTER = 'no_filter';
    public const string NAME = 'name';
    public const string TOO_MANY = 'too_many';
    public const string TOO_BIG = 'too_big';
    public const string NOT_FOUND = 'not_found';
    public const string NOTHING_LEFT = 'nothing_left';

    public function __construct(public readonly string $reason, public readonly int $limit = 0)
    {
        parent::__construct('Saved filters: ' . $reason);
    }
}
