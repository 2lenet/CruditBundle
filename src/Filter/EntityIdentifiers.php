<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Filter;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;

/**
 * The identifiers of an entity as a query takes them: the ids a filter keeps are strings (`"12"`, the text of a UUID),
 * the column may be an integer or a binary UUID. `find()` and `findBy()` convert them by the type of the field; a DQL
 * `IN (:ids)` does not, so it is done here, with the type of the list of parameters.
 */
final class EntityIdentifiers
{
    /**
     * @param class-string $class
     * @param list<string> $ids
     *
     * @return array{list<mixed>, mixed} the values for the database (the ones that do not fit the type are left out) and the type of the list
     *
     * @throws \Doctrine\ORM\Mapping\MappingException when the identifier is composite
     */
    public static function forQuery(EntityManagerInterface $entityManager, string $class, array $ids): array
    {
        $metadata = $entityManager->getClassMetadata($class);
        $field = $metadata->getSingleIdentifierFieldName();
        $type = Type::getType($metadata->getTypeOfField($field) ?? 'string');
        $platform = $entityManager->getConnection()->getDatabasePlatform();

        $values = [];
        foreach ($ids as $id) {
            try {
                $values[] = $type->convertToDatabaseValue($type->convertToPHPValue($id, $platform), $platform);
            } catch (\Throwable) {
                // Not an identifier of this type (a stale value of a filter): nothing to find.
            }
        }

        $name = match ($type->getBindingType()) {
            ParameterType::INTEGER => 'INTEGER',
            ParameterType::BINARY => 'BINARY',
            ParameterType::ASCII => 'ASCII',
            default => 'STRING',
        };

        // DBAL 3.6 and later: ArrayParameterType (a constant, or a case of the enum of DBAL 4); before, Connection::PARAM_*_ARRAY.
        return [$values, constant(class_exists(ArrayParameterType::class) ? ArrayParameterType::class . '::' . $name : Connection::class . '::PARAM_' . ($name === 'INTEGER' ? 'INT' : 'STR') . '_ARRAY')];
    }
}
