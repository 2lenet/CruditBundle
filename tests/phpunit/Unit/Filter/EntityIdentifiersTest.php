<?php

declare(strict_types=1);

namespace phpunit\Unit\Filter;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Types\BinaryType;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Lle\CruditBundle\Filter\EntityIdentifiers;
use PHPUnit\Framework\TestCase;

/**
 * The identifiers a filter keeps (strings) as a `IN (:ids)` takes them, by the type of the identifier.
 */
class EntityIdentifiersTest extends TestCase
{
    private function manager(string $type): EntityManagerInterface
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getSingleIdentifierFieldName')->willReturn('id');
        $metadata->method('getTypeOfField')->willReturn($type);
        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn(new MySQLPlatform());
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getClassMetadata')->willReturn($metadata);
        $manager->method('getConnection')->willReturn($connection);

        return $manager;
    }

    public function testAnIntegerIdentifier(): void
    {
        [$values, $type] = EntityIdentifiers::forQuery($this->manager('integer'), \stdClass::class, ['12', '7']);

        self::assertSame([12, 7], $values);
        self::assertSame(ArrayParameterType::INTEGER, $type);
    }

    public function testAStringIdentifier(): void
    {
        [$values, $type] = EntityIdentifiers::forQuery($this->manager('string'), \stdClass::class, ['a', 'b']);

        self::assertSame(['a', 'b'], $values);
        self::assertSame(ArrayParameterType::STRING, $type);
    }

    public function testABinaryUuidIsFoundByItsText(): void
    {
        if (!Type::hasType('binary_uuid_test')) {
            Type::addType('binary_uuid_test', BinaryUuidTestType::class);
        }
        $uuid = '0b8e1f6e-2a4c-4f5b-9a3d-6c7e8f901234';

        [$values, $type] = EntityIdentifiers::forQuery($this->manager('binary_uuid_test'), \stdClass::class, [$uuid, 'not a uuid']);

        self::assertSame([hex2bin(str_replace('-', '', $uuid))], $values, 'the text becomes the 16 bytes of the column, what is not a UUID is left out');
        self::assertSame(ArrayParameterType::BINARY, $type);
    }
}

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
