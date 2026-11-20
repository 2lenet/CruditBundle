<?php

declare(strict_types=1);

namespace phpunit\Unit\Form\DataTransformer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Lle\CruditBundle\Form\DataTransformer\EntityToIdTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * The value of AutocompleteType: an id, or the ids joined by a comma with "multiple" (the hidden input of the list with
 * search of CruditUiBundle).
 */
class EntityToIdTransformerTest extends TestCase
{
    /** @var array<int, object> */
    private array $clients;

    protected function setUp(): void
    {
        $this->clients = [3 => $this->client(3), 7 => $this->client(7)];
    }

    public function testAnEntityBecomesItsId(): void
    {
        self::assertSame(3, $this->transformer(false)->transform($this->clients[3]));
        self::assertNull($this->transformer(false)->transform(null));
    }

    public function testSeveralEntitiesBecomeTheirIdsJoined(): void
    {
        self::assertSame('3,7', $this->transformer(true)->transform([$this->clients[3], $this->clients[7]]));
        self::assertSame('', $this->transformer(true)->transform(null));
    }

    public function testAnIdBecomesItsEntity(): void
    {
        self::assertSame($this->clients[7], $this->transformer(false)->reverseTransform('7'));
    }

    public function testTheIdsJoinedBecomeTheirEntities(): void
    {
        self::assertSame([$this->clients[3], $this->clients[7]], $this->transformer(true)->reverseTransform('3,7'));
    }

    public function testTheSameIdTwiceIsOneEntity(): void
    {
        self::assertSame([$this->clients[3]], $this->transformer(true)->reverseTransform('3,3'));
    }

    public function testNoValueIsNoEntity(): void
    {
        self::assertNull($this->transformer(false)->reverseTransform(''));
        self::assertSame([], $this->transformer(true)->reverseTransform(''));
    }

    public function testAnUnknownIdIsRefused(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->transformer(false)->reverseTransform('9');
    }

    public function testAnUnknownIdAmongTheIdsJoinedIsRefused(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->transformer(true)->reverseTransform('3,9');
    }

    private function transformer(bool $multiple): EntityToIdTransformer
    {
        $clients = $this->clients;
        $repository = $this->createStub(EntityRepository::class);
        $repository->method('find')->willReturnCallback(fn (mixed $id) => $clients[(int) $id] ?? null);
        $repository->method('findBy')->willReturnCallback(fn (array $criteria) => array_values(array_filter(
            array_map(fn (mixed $id) => $clients[(int) $id] ?? null, array_unique($criteria['id'])),
        )));
        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);

        return (new EntityToIdTransformer($em))->setClass(\stdClass::class)->setMultiple($multiple);
    }

    private function client(int $id): object
    {
        return new class ($id) {
            public function __construct(private readonly int $id)
            {
            }

            public function getId(): int
            {
                return $this->id;
            }
        };
    }
}
