<?php

declare(strict_types=1);

namespace phpunit\Unit\Filter;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;

/**
 * Like the type of ramsey/uuid-doctrine: the PHP value is made from the bytes (a text of 36 characters is refused), the
 * database value is made from the text.
 */
class BinaryUuidBytesTestType extends BinaryUuidTestType
{
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (!is_string($value) || strlen($value) !== 16) {
            throw new ConversionException('Not 16 bytes');
        }

        return $value;
    }
}
