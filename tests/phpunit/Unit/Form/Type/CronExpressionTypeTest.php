<?php

declare(strict_types=1);

namespace phpunit\Unit\Form\Type;

use Lle\CruditBundle\Form\Type\CronExpressionType;
use Lle\CruditBundle\Validator\CronExpression;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validation;

class CronExpressionTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        return [new ValidatorExtension(Validation::createValidator())];
    }

    /**
     * @return iterable<string, array{?string, array<string, mixed>}>
     */
    public static function expressions(): iterable
    {
        yield 'empty' => [null, ['frequency' => 'none']];
        yield 'blank' => ['  ', ['frequency' => 'none']];
        yield 'every day' => ['0 7 * * *', ['frequency' => 'daily', 'time' => '07:00']];
        yield 'days of the week' => ['30 8 * * 1,4', ['frequency' => 'weekly', 'days' => ['1', '4'], 'time' => '08:30']];
        yield 'Sunday, a day twice' => ['5 23 * * 0,0', ['frequency' => 'weekly', 'days' => ['0'], 'time' => '23:05']];
        yield 'a day of the month' => ['0 18 28 * *', ['frequency' => 'monthly', 'month_day' => '28', 'time' => '18:00']];
        yield 'every day, zeros before' => ['00 08 * * *', ['frequency' => 'daily', 'time' => '08:00']];
        yield 'days of the week, zeros before' => ['05 14 * * 2', ['frequency' => 'weekly', 'days' => ['2'], 'time' => '14:05']];
        yield 'a day of the month, zeros before' => ['0 9 05 * *', ['frequency' => 'monthly', 'month_day' => '5', 'time' => '09:00']];
        yield 'the last day of the month, not standard cron' => ['0 18 L * *', ['frequency' => 'custom']];
        yield 'a day the widget does not offer' => ['0 18 31 * *', ['frequency' => 'custom']];
        yield 'Sunday as 7' => ['0 8 * * 7', ['frequency' => 'custom']];
        yield 'a range' => ['*/15 6-20 * * 1-5', ['frequency' => 'custom']];
        yield 'a month' => ['0 8 1 6 *', ['frequency' => 'custom']];
        yield 'a minute out of range' => ['60 8 * * *', ['frequency' => 'custom']];
        yield 'an hour out of range' => ['0 24 * * *', ['frequency' => 'custom']];
        yield 'a nickname' => ['@daily', ['frequency' => 'custom']];
        yield 'four fields' => ['30 8 * *', ['frequency' => 'custom']];
    }

    /**
     * @param array<string, mixed> $expected
     */
    #[DataProvider('expressions')]
    public function testTheStateOfTheWidget(?string $expression, array $expected): void
    {
        $state = CronExpressionType::widgetState($expression);

        self::assertSame(array_merge(['frequency' => 'custom', 'days' => [], 'month_day' => '1', 'time' => '08:00'], $expected), $state);
    }

    public function testTheStateIsInTheView(): void
    {
        $view = $this->factory->create(CronExpressionType::class, '0 18 15 * *')->createView();

        self::assertSame('monthly', $view->vars['crudit_cron_state']['frequency']);
        self::assertSame('crudit_cron_expression', $view->vars['block_prefixes'][2]);
        self::assertNull($view->vars['label'], 'a label of its own, no more label: false');
    }

    public function testTheConstraintIsAlwaysAddedOnce(): void
    {
        $constraints = $this->factory->create(CronExpressionType::class, null, ['constraints' => [new NotBlank()]])->getConfig()->getOption('constraints');
        self::assertCount(2, $constraints);
        self::assertInstanceOf(CronExpression::class, $constraints[1]);

        $own = new CronExpression(message: 'Not a recurrence.');
        self::assertSame([$own], $this->factory->create(CronExpressionType::class, null, ['constraints' => $own])->getConfig()->getOption('constraints'));
    }

    public function testNotRequiredByDefault(): void
    {
        $form = $this->factory->create(CronExpressionType::class);
        self::assertFalse($form->getConfig()->getOption('required'));
        self::assertSame([CronExpression::class], array_map(fn (object $c) => $c::class, $form->getConfig()->getOption('constraints')));
        $form->submit('');
        self::assertTrue($form->isValid(), 'an empty value passes when not required');
    }

    public function testRequiredRefusesAnEmptyValue(): void
    {
        $form = $this->factory->create(CronExpressionType::class, null, ['required' => true]);
        $form->submit('');

        self::assertFalse($form->isValid());
        self::assertInstanceOf(NotBlank::class, $form->getErrors()[0]->getCause()?->getConstraint());

        // Its own NotBlank is not doubled
        $own = new NotBlank(message: 'A recurrence, please.');
        $constraints = $this->factory->create(CronExpressionType::class, null, ['required' => true, 'constraints' => $own])->getConfig()->getOption('constraints');
        self::assertSame([$own], array_slice($constraints, 0, 1));
        self::assertCount(2, $constraints);
    }

    public function testAnInvalidExpressionIsRefused(): void
    {
        $form = $this->factory->create(CronExpressionType::class);
        $form->submit('30 8 * *');

        self::assertFalse($form->isValid());
        self::assertSame('crudit.cron.invalid', $form->getErrors()[0]->getMessageTemplate());
    }
}
