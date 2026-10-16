<?php

declare(strict_types=1);

namespace phpunit\Unit\Brick;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Gedmo\Loggable\Entity\LogEntry;
use Gedmo\Loggable\Entity\Repository\LogEntryRepository;
use Lle\CruditBundle\Brick\HistoryBrick\HistoryConfig;
use Lle\CruditBundle\Brick\HistoryBrick\HistoryFactory;
use Lle\CruditBundle\Contracts\DatasourceInterface;
use Lle\CruditBundle\Provider\ConfigProvider;
use Lle\CruditBundle\Resolver\ResourceResolver;
use Lle\CruditUiBundle\Format\Formatter;
use Lle\CruditUiBundle\Locale\RequestLocale;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use Twig\Extension\CoreExtension;
use Twig\Loader\ArrayLoader;

/**
 * The values of the history are formatted by the Formatter of CruditUiBundle, as the date of the log entry
 * (`ui_datetime` in the template): same locale, same timezone.
 */
class HistoryFactoryTest extends TestCase
{
    private string $defaultLocale;

    protected function setUp(): void
    {
        $this->defaultLocale = \Locale::getDefault();
    }

    protected function tearDown(): void
    {
        // Request::setLocale() changes the default locale of PHP
        \Locale::setDefault($this->defaultLocale);
    }

    public function testDatesAndTimesFollowTheLocaleAndTheTimezoneOfTwig(): void
    {
        $utc = new \DateTimeZone('UTC');
        $values = $this->values([
            // a date alone keeps its day: at midnight in UTC, it would be the day before in New York
            'deliveryDate' => ['date', new \DateTime('2026-10-13 00:00', $utc)],
            'openingTime' => ['time', new \DateTime('1970-01-01 14:05', $utc)],
            'updatedAt' => ['datetime_immutable', new \DateTimeImmutable('2026-10-13 14:05', $utc)],
            'scannedAt' => [null, new \DateTimeImmutable('2026-10-13 14:05', $utc)],
        ]);

        // the spaces depend on the version of ICU: only the digits, their order and the half of the day are checked
        self::assertDigits(['10', '13', '2026'], $values['deliveryDate']);
        self::assertDigits(['2', '05', '00'], $values['openingTime']);
        self::assertStringContainsString('PM', $values['openingTime']);
        foreach (['updatedAt', 'scannedAt'] as $field) {
            self::assertDigits(['10', '13', '2026', '10', '05', '00'], $values[$field]);
            self::assertStringContainsString('AM', $values[$field]);
        }
    }

    /**
     * @param list<string> $numbers
     */
    private static function assertDigits(array $numbers, string $actual): void
    {
        preg_match_all('/\d+/', $actual, $matches);
        self::assertSame($numbers, $matches[0], $actual);
    }

    /**
     * Values of a log entry as the brick shows them, in English, Twig being in the timezone of New York.
     *
     * @param array<string, array{?string, mixed}> $fields type and value of each field
     *
     * @return array<string, string>
     */
    private function values(array $fields): array
    {
        $request = new Request();
        $request->setLocale('en');
        $request->attributes->set('id', '1');
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $twig = new Environment(new ArrayLoader());
        $twig->getExtension(CoreExtension::class)->setTimezone('America/New_York');

        $log = new LogEntry();
        $log->setObjectClass(\stdClass::class);
        $log->setData(array_map(static fn (array $field): mixed => $field[1], $fields));

        $repository = $this->createMock(LogEntryRepository::class);
        $repository->method('getLogEntries')->willReturn([$log]);
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getTypeOfField')->willReturnCallback(static fn (string $field): ?string => $fields[$field][0]);
        $metadata->method('hasAssociation')->willReturn(false);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);
        $em->method('getClassMetadata')->willReturn($metadata);

        $factory = new HistoryFactory(
            $this->createMock(ResourceResolver::class),
            $requestStack,
            $em,
            $this->createMock(ConfigProvider::class),
            new Formatter(new RequestLocale($requestStack, 'en'), $twig),
        );
        $datasource = $this->createMock(DatasourceInterface::class);
        $datasource->method('get')->willReturn(new \stdClass());
        $config = $this->createMock(HistoryConfig::class);
        $config->method('getDatasource')->willReturn($datasource);
        $config->method('getOptions')->willReturn([]);

        /** @var array{history: list<array{data: array<string, array{value: mixed}>}>} $data */
        $data = $factory->buildView($config)->getData();

        return array_map(static fn (array $value): mixed => $value['value'], $data['history'][0]['data']);
    }
}
