<?php

namespace phpunit\Unit\Form\DataTransformer;

use Lle\CruditBundle\Form\DataTransformer\TelephoneTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

class TelephoneTransformerTest extends TestCase
{
    private TelephoneTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new TelephoneTransformer('FR');
    }

    /**
     * @dataProvider validInputs
     */
    public function testReverseTransform(string $country, string $number, string $expected): void
    {
        self::assertSame($expected, $this->transformer->reverseTransform(['country' => $country, 'number' => $number]));
    }

    public static function validInputs(): iterable
    {
        yield 'french mobile with trunk prefix' => ['FR', '06 12 34 56 78', '+33612345678'];
        yield 'french mobile without trunk prefix' => ['FR', '612345678', '+33612345678'];
        yield 'swiss mobile with dots' => ['CH', '079.123.45.67', '+41791234567'];
        yield 'italian landline keeps its zero' => ['IT', '06 1234 5678', '+390612345678'];
        yield 'italian zero kept after a typed +39' => ['FR', '+39 06 1234 5678', '+390612345678'];
        yield 'no calling code but international prefix' => ['', '+41 79 123 45 67', '+41791234567'];
        yield 'us number with dashes and parentheses' => ['US', '(212) 555-1234', '+12125551234'];
        yield 'international prefix wins over select' => ['CH', '+33 6 12 34 56 78', '+33612345678'];
        yield '00 international prefix' => ['FR', '0041 79 123 45 67', '+41791234567'];
        yield '(0) notation' => ['FR', '+41 (0)79 123 45 67', '+41791234567'];
        yield 'country without known lengths' => ['JP', '90 1234 5678', '+819012345678'];
    }

    /**
     * @dataProvider invalidInputs
     */
    public function testReverseTransformFails(string $country, string $number, string $message): void
    {
        try {
            $this->transformer->reverseTransform(['country' => $country, 'number' => $number]);
            self::fail('TransformationFailedException expected');
        } catch (TransformationFailedException $e) {
            self::assertSame($message, $e->getInvalidMessage());
        }
    }

    public static function invalidInputs(): iterable
    {
        yield 'letters' => ['FR', '06 12 AB 56 78', 'crudit.telephone.invalid_characters'];
        yield 'plus in the middle' => ['FR', '06+12345678', 'crudit.telephone.invalid_characters'];
        yield 'french number too short' => ['FR', '06 12 34 56', 'crudit.telephone.invalid_length'];
        yield 'swiss number too long' => ['CH', '079 123 45 67 8', 'crudit.telephone.invalid_length'];
        yield 'belgian number out of range' => ['BE', '1234567', 'crudit.telephone.invalid_length_range'];
        yield 'unknown calling code' => ['FR', '+999 123 456', 'crudit.telephone.invalid'];
        yield 'no calling code' => ['', '06 12 34 56 78', 'crudit.telephone.calling_code_required'];
    }

    public function testEmptyNumberIsNull(): void
    {
        self::assertNull($this->transformer->reverseTransform(['country' => 'FR', 'number' => '  ']));
        self::assertNull($this->transformer->reverseTransform(null));
    }

    public function testTransform(): void
    {
        self::assertSame(['country' => 'CH', 'number' => '791234567'], $this->transformer->transform('+41791234567'));
        self::assertSame(['country' => 'FR', 'number' => null], $this->transformer->transform(null));
    }

    public function testSharedCallingCodeUsesDefaultCountryAsHint(): void
    {
        self::assertSame(['country' => 'US', 'number' => '2125551234'], $this->transformer->transform('+12125551234'));
        self::assertSame(
            ['country' => 'CA', 'number' => '4165551234'],
            (new TelephoneTransformer('CA'))->transform('+14165551234'),
        );
    }

    public function testLegacyValueHasNoCallingCode(): void
    {
        self::assertSame(
            ['country' => null, 'number' => '06 12 34 56 78'],
            $this->transformer->transform('06 12 34 56 78'),
        );
    }

    public function testUnknownCallingCodeIsLegacy(): void
    {
        self::assertSame(['country' => null, 'number' => '+999123456'], $this->transformer->transform('+999123456'));
    }

    public function testUntouchedLegacyValueIsKeptAsIs(): void
    {
        $this->transformer->transform('06 12 34 56 78 (bureau)');

        self::assertSame(
            '06 12 34 56 78 (bureau)',
            $this->transformer->reverseTransform(['country' => null, 'number' => '06 12 34 56 78 (bureau)']),
        );
    }

    public function testLegacyValueIsConvertedOnceACallingCodeIsChosen(): void
    {
        $this->transformer->transform('06 12 34 56 78');

        self::assertSame(
            '+33612345678',
            $this->transformer->reverseTransform(['country' => 'FR', 'number' => '06 12 34 56 78']),
        );
    }
}
