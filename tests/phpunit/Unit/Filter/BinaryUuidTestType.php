<?php

declare(strict_types=1);

namespace phpunit\Unit\Filter;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\BinaryType;
use Doctrine\DBAL\Types\ConversionException;

/**
 * A UUID stored as 16 bytes, like the Uuid type of Symfony on MySQL: the text of the UUID in PHP, binary in the database.
 */
class BinaryUuidTestType extends BinaryType
{
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?string
    {
        return $value;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): mixed
    {
        if (!is_string($value) || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $value)) {
            throw new ConversionException('Not a UUID');
        }

        return hex2bin(str_replace('-', '', $value));
    }
}
