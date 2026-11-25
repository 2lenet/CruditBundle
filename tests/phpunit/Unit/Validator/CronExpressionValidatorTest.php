<?php

declare(strict_types=1);

namespace phpunit\Unit\Validator;

use Lle\CruditBundle\Validator\CronExpression;
use Lle\CruditBundle\Validator\CronExpressionValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Loader\YamlFileLoader;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<CronExpressionValidator>
 */
class CronExpressionValidatorTest extends ConstraintValidatorTestCase
{
    /** A property for the mapping in YAML */
    public ?string $cron = null;

    protected function createValidator(): CronExpressionValidator
    {
        return new CronExpressionValidator();
    }

    /**
     * @return iterable<array{?string}>
     */
    public static function valid(): iterable
    {
        yield [null];
        yield [''];
        yield ['30 8 * * 1,4'];
        yield ['0 18 L * *'];
        yield ['*/15 6-20 * * 1-5'];
        yield ['@daily'];
    }

    #[DataProvider('valid')]
    public function testValid(?string $value): void
    {
        $this->check($value);

        $this->assertNoViolation();
    }

    /**
     * @return iterable<array{string}>
     */
    public static function invalid(): iterable
    {
        yield ['30 8 * *'];
        // The widget with an empty time
        yield ['* * * 2'];
        yield ['61 8 * * *'];
        yield ['every day'];
    }

    #[DataProvider('invalid')]
    public function testInvalid(string $value): void
    {
        $this->check($value);

        $this->buildViolation('crudit.cron.invalid')
            ->setParameter('{{ value }}', '"' . $value . '"')
            ->setCode(CronExpression::INVALID_ERROR)
            ->assertRaised();
    }

    public function testNotAString(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->check(8);
    }

    public function testTheConstraintInYaml(): void
    {
        // The options of the loader given as named arguments (HasNamedArguments), not as an array in $message
        $file = tempnam(sys_get_temp_dir(), 'cron') . '.yaml';
        file_put_contents($file, self::class . ":\n    properties:\n        cron:\n            - Lle\\CruditBundle\\Validator\\CronExpression: {message: 'Not a recurrence.', groups: [Foo]}\n");
        $metadata = new ClassMetadata(self::class);
        try {
            (new YamlFileLoader($file))->loadClassMetadata($metadata);
        } finally {
            unlink($file);
        }

        $constraint = $metadata->getPropertyMetadata('cron')[0]->getConstraints()[0];
        self::assertInstanceOf(CronExpression::class, $constraint);
        self::assertSame('Not a recurrence.', $constraint->message);
        self::assertSame(['Foo'], $constraint->groups);
    }

    private function check(mixed $value): void
    {
        // validateInContext() since Symfony 8.1 (validate() alone is deprecated there), validate() before
        if (method_exists($this->validator, 'validateInContext')) { // @phpstan-ignore function.alreadyNarrowedType (true with Symfony 8.1)
            $this->validator->validateInContext($value, new CronExpression(), $this->context);

            return;
        }
        $this->validator->validate($value, new CronExpression()); // @phpstan-ignore method.deprecated (Symfony 7)
    }
}
