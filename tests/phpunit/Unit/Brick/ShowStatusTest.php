<?php

declare(strict_types=1);

namespace phpunit\Unit\Brick;

use Lle\CruditBundle\Brick\LinksBrick\LinksConfig;
use Lle\CruditBundle\Brick\ShowBrick\ShowConfig;
use Lle\CruditBundle\Brick\ShowBrick\ShowStatus;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Field\TextField;
use Lle\CruditBundle\Field\WorkflowField;
use Lle\CruditBundle\Registry\FieldRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * The status of a record (lot 3.3.a): the first workflow field of getFields(SHOW), groups included, declared as such
 * (Doctrine guesses a string); none when isShowStatusInHeader() is false.
 */
class ShowStatusTest extends TestCase
{
    public function testTheFirstWorkflowField(): void
    {
        $status = Field::new('status', 'workflow');
        $config = $this->config([
            Field::new('name'),
            'section.state' => [Field::new('reference', TextField::class), $status, Field::new('step', WorkflowField::class)],
        ]);

        self::assertSame($status, $this->showStatus()->find($config));
    }

    public function testNoWorkflowField(): void
    {
        self::assertNull($this->showStatus()->find($this->config([Field::new('status'), Field::new('name', 'unknown')])));
    }

    public function testTheStatusCanStayInItsSection(): void
    {
        self::assertNull($this->showStatus()->find($this->config([Field::new('status', 'workflow')], false)));
    }

    public function testThePageOfTheRecordHasAHeader(): void
    {
        $config = $this->config([]);
        $config->method('getBrickConfigs')->willReturn([CrudConfigInterface::SHOW => [ShowConfig::new()]]);

        self::assertFalse($this->showStatus()->hasHeader($config));
        self::assertTrue($this->showStatus()->hasHeader($this->withHeader()));
    }

    /**
     * @param array<Field|Field[]> $fields
     */
    private function config(array $fields, bool $inHeader = true): CrudConfigInterface&\PHPUnit\Framework\MockObject\Stub
    {
        $config = $this->createStub(CrudConfigInterface::class);
        $config->method('getFields')->willReturn($fields);
        $config->method('isShowStatusInHeader')->willReturn($inHeader);

        return $config;
    }

    private function withHeader(): CrudConfigInterface
    {
        $config = $this->createStub(CrudConfigInterface::class);
        $config->method('getBrickConfigs')->willReturn([CrudConfigInterface::SHOW => [LinksConfig::new(), ShowConfig::new()]]);

        return $config;
    }

    private function showStatus(): ShowStatus
    {
        $twig = new Environment(new ArrayLoader());
        $parameters = new ParameterBag();

        return new ShowStatus(new FieldRegistry([new TextField($twig, $parameters), new WorkflowField($twig, $parameters)]));
    }
}
