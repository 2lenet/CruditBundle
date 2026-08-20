<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Datasource;

class RuptDateFormat
{
    private const array PHP_TO_SQL = [
        'Y' => '%Y',
        'y' => '%y',
        'm' => '%m',
        'n' => '%c',
        'd' => '%d',
        'j' => '%e',
        'H' => '%H',
        'h' => '%h',
        'i' => '%i',
        's' => '%s',
    ];

    public static function toSql(string $phpFormat): string
    {
        $result = '';
        foreach (str_split($phpFormat) as $char) {
            $result .= self::PHP_TO_SQL[$char] ?? $char;
        }

        return $result;
    }
}
