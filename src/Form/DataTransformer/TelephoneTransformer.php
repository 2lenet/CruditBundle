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
 * @implements DataTransformerInterface<string, array{country: ?string, number: ?string}>
 */
class TelephoneTransformer implements DataTransformerInterface
{
    /** Value of the form that is not E.164 (legacy free text), see reverseTransform() */
    private ?string $legacyValue = null;

    public function __construct(private readonly string $defaultCountry)
    {
    }

    public function transform(mixed $value): array
    {
        $this->legacyValue = null;
        if ($value === null || $value === '') {
            return ['country' => $this->defaultCountry, 'number' => null];
        }

        $split = str_starts_with($value, '+')
            ? CallingCodes::split(substr($value, 1), $this->defaultCountry)
            : null;

        // Legacy free text ("06 12 34 56 78"): its calling code is unknown, so none is selected rather than guessed
        if ($split === null) {
            $this->legacyValue = $value;

            return ['country' => null, 'number' => $value];
        }

        return ['country' => $split[0], 'number' => $split[1]];
    }

    public function reverseTransform(mixed $value): ?string
    {
        $input = trim((string)($value['number'] ?? ''));
        if ($input === '') {
            return null;
        }

        $country = (string)($value['country'] ?? '');

        // Legacy value submitted untouched without calling code: kept as is, so that saving
        // the rest of the form neither fails nor turns it into a wrong number
        if ($country === '' && $this->legacyValue !== null && $input === trim($this->legacyValue)) {
            return $this->legacyValue;
        }

        if (!preg_match('/^\+?[\d\s.\-\/()]+$/', $input)) {
            throw $this->failure('crudit.telephone.invalid_characters');
        }

        $digits = (string)preg_replace('/\D/', '', $input);

        // An international prefix typed in the number wins over the selected country
        if (str_starts_with($input, '+') || str_starts_with($digits, '00')) {
            $split = CallingCodes::split(ltrim($digits, '0'), $country ?: $this->defaultCountry);
            if ($split === null) {
                throw $this->failure('crudit.telephone.invalid');
            }
            [$country, $national] = $split;
        } elseif ($country === '') {
            throw $this->failure('crudit.telephone.calling_code_required');
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

    private function failure(string $message, ?string $country = null): TransformationFailedException
    {
        $parameters = [];
        if ($country !== null) {
            [$min, $max] = CallingCodes::getNationalLengths($country);
            $parameters = [
                '{{ country }}' => Countries::exists($country) ? Countries::getName($country) : $country,
                '{{ code }}' => '+' . CallingCodes::getCode($country),
                '{{ min }}' => (string)$min,
                '{{ max }}' => (string)$max,
            ];
        }

        $exception = new TransformationFailedException($message);
        $exception->setInvalidMessage($message, $parameters);

        return $exception;
    }
}
