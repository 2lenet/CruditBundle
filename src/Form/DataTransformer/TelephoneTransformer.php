<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Form\DataTransformer;

use Lle\CruditBundle\Form\Telephone\CallingCodes;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Intl\Countries;

/**
 * Converts an E.164 string ("+41791234567") to/from the [country, number] pair of TelephoneType.
 *
 * @implements DataTransformerInterface<string, array{country: string, number: ?string}>
 */
class TelephoneTransformer implements DataTransformerInterface
{
    public function __construct(private readonly string $defaultCountry)
    {
    }

    public function transform(mixed $value): array
    {
        if ($value === null || $value === '') {
            return ['country' => $this->defaultCountry, 'number' => null];
        }

        $split = str_starts_with($value, '+')
            ? CallingCodes::split(substr($value, 1), $this->defaultCountry)
            : null;

        // Legacy free text (e.g. "06 12 34 56 78") is shown as is, so it gets fixed on next save
        if ($split === null) {
            return ['country' => $this->defaultCountry, 'number' => $value];
        }

        return ['country' => $split[0], 'number' => $split[1]];
    }

    public function reverseTransform(mixed $value): ?string
    {
        $input = trim((string)($value['number'] ?? ''));
        if ($input === '') {
            return null;
        }

        $country = (string)($value['country'] ?? '') ?: $this->defaultCountry;
        if (!preg_match('/^\+?[\d\s.\-\/()]+$/', $input)) {
            throw $this->failure('crudit.telephone.invalid_characters', $country);
        }

        $digits = (string)preg_replace('/\D/', '', $input);

        // An international prefix typed in the number wins over the selected country
        if (str_starts_with($input, '+') || str_starts_with($digits, '00')) {
            $split = CallingCodes::split(ltrim($digits, '0'), $country);
            if ($split === null) {
                throw $this->failure('crudit.telephone.invalid', $country);
            }
            [$country, $national] = $split;
        } else {
            $national = $digits;
        }

        // Trunk prefix: "06…" in France, or the common "+33 (0)6…" notation
        if (str_starts_with($national, '0') && !CallingCodes::keepsLeadingZero($country)) {
            $national = substr($national, 1);
        }

        [$min, $max] = CallingCodes::getNationalLengths($country);
        $length = strlen($national);
        if ($length < $min || $length > $max) {
            throw $this->failure(
                $min === $max ? 'crudit.telephone.invalid_length' : 'crudit.telephone.invalid_length_range',
                $country,
            );
        }

        return '+' . CallingCodes::getCode($country) . $national;
    }

    private function failure(string $message, string $country): TransformationFailedException
    {
        [$min, $max] = CallingCodes::getNationalLengths($country);

        $exception = new TransformationFailedException($message);
        $exception->setInvalidMessage($message, [
            '{{ country }}' => Countries::exists($country) ? Countries::getName($country) : $country,
            '{{ code }}' => '+' . CallingCodes::getCode($country),
            '{{ min }}' => (string)$min,
            '{{ max }}' => (string)$max,
        ]);

        return $exception;
    }
}
