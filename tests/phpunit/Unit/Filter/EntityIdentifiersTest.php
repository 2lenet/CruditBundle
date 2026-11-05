<?php

declare(strict_types=1);

namespace phpunit\Unit\Filter;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Lle\CruditBundle\Filter\EntityIdentifiers;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The identifiers a filter keeps (strings) as a `IN (:ids)` takes them, by the type of the identifier.
 */
class EntityIdentifiersTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        // The test has no autoload of its own: the types it registers are loaded here.
        require_once __DIR__ . '/BinaryUuidTestType.php';
        require_once __DIR__ . '/BinaryUuidBytesTestType.php';
    }

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

    /**
     * @return iterable<string, array{string}>
     */
    public static function binaryUuidTypes(): iterable
    {
        yield 'reads the text or the bytes (the Uuid type of Symfony)' => ['binary_uuid_test'];
        yield 'reads the bytes only (uuid_binary of ramsey/uuid-doctrine)' => ['binary_uuid_bytes_test'];
    }

    #[DataProvider('binaryUuidTypes')]
    public function testABinaryUuidIsFoundByItsText(string $typeName): void
    {
        foreach (['binary_uuid_test' => BinaryUuidTestType::class, 'binary_uuid_bytes_test' => BinaryUuidBytesTestType::class] as $name => $class) {
            if (!Type::hasType($name)) {
                Type::addType($name, $class);
            }
        }
        $uuid = '0b8e1f6e-2a4c-4f5b-9a3d-6c7e8f901234';

        [$values, $type] = EntityIdentifiers::forQuery($this->manager($typeName), \stdClass::class, [$uuid, 'not a uuid']);

        self::assertSame([hex2bin(str_replace('-', '', $uuid))], $values, 'the text becomes the 16 bytes of the column, what is not a UUID is left out');
        self::assertSame(ArrayParameterType::BINARY, $type);
    }
}
