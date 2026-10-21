<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Form\Telephone;

/**
 * Country calling codes (ITU E.164) and national number lengths, without external dependency.
 */
final class CallingCodes
{
    /** E.164 caps a full number (calling code included) at 15 digits */
    public const MAX_DIGITS = 15;

    public const MIN_NATIONAL_DIGITS = 4;

    /** @var array<string, string> ISO 3166-1 alpha-2 => calling code */
    public const CODES = [
        'AD' => '376', 'AE' => '971', 'AF' => '93', 'AG' => '1', 'AI' => '1', 'AL' => '355', 'AM' => '374',
        'AO' => '244', 'AR' => '54', 'AS' => '1', 'AT' => '43', 'AU' => '61', 'AW' => '297', 'AX' => '358',
        'AZ' => '994', 'BA' => '387', 'BB' => '1', 'BD' => '880', 'BE' => '32', 'BF' => '226', 'BG' => '359',
        'BH' => '973', 'BI' => '257', 'BJ' => '229', 'BL' => '590', 'BM' => '1', 'BN' => '673', 'BO' => '591',
        'BQ' => '599', 'BR' => '55', 'BS' => '1', 'BT' => '975', 'BW' => '267', 'BY' => '375', 'BZ' => '501',
        'CA' => '1', 'CC' => '61', 'CD' => '243', 'CF' => '236', 'CG' => '242', 'CH' => '41', 'CI' => '225',
        'CK' => '682', 'CL' => '56', 'CM' => '237', 'CN' => '86', 'CO' => '57', 'CR' => '506', 'CU' => '53',
        'CV' => '238', 'CW' => '599', 'CX' => '61', 'CY' => '357', 'CZ' => '420', 'DE' => '49', 'DJ' => '253',
        'DK' => '45', 'DM' => '1', 'DO' => '1', 'DZ' => '213', 'EC' => '593', 'EE' => '372', 'EG' => '20',
        'EH' => '212', 'ER' => '291', 'ES' => '34', 'ET' => '251', 'FI' => '358', 'FJ' => '679', 'FK' => '500',
        'FM' => '691', 'FO' => '298', 'FR' => '33', 'GA' => '241', 'GB' => '44', 'GD' => '1', 'GE' => '995',
        'GF' => '594', 'GG' => '44', 'GH' => '233', 'GI' => '350', 'GL' => '299', 'GM' => '220', 'GN' => '224',
        'GP' => '590', 'GQ' => '240', 'GR' => '30', 'GT' => '502', 'GU' => '1', 'GW' => '245', 'GY' => '592',
        'HK' => '852', 'HN' => '504', 'HR' => '385', 'HT' => '509', 'HU' => '36', 'ID' => '62', 'IE' => '353',
        'IL' => '972', 'IM' => '44', 'IN' => '91', 'IO' => '246', 'IQ' => '964', 'IR' => '98', 'IS' => '354',
        'IT' => '39', 'JE' => '44', 'JM' => '1', 'JO' => '962', 'JP' => '81', 'KE' => '254', 'KG' => '996',
        'KH' => '855', 'KI' => '686', 'KM' => '269', 'KN' => '1', 'KP' => '850', 'KR' => '82', 'KW' => '965',
        'KY' => '1', 'KZ' => '7', 'LA' => '856', 'LB' => '961', 'LC' => '1', 'LI' => '423', 'LK' => '94',
        'LR' => '231', 'LS' => '266', 'LT' => '370', 'LU' => '352', 'LV' => '371', 'LY' => '218', 'MA' => '212',
        'MC' => '377', 'MD' => '373', 'ME' => '382', 'MF' => '590', 'MG' => '261', 'MH' => '692', 'MK' => '389',
        'ML' => '223', 'MM' => '95', 'MN' => '976', 'MO' => '853', 'MP' => '1', 'MQ' => '596', 'MR' => '222',
        'MS' => '1', 'MT' => '356', 'MU' => '230', 'MV' => '960', 'MW' => '265', 'MX' => '52', 'MY' => '60',
        'MZ' => '258', 'NA' => '264', 'NC' => '687', 'NE' => '227', 'NF' => '672', 'NG' => '234', 'NI' => '505',
        'NL' => '31', 'NO' => '47', 'NP' => '977', 'NR' => '674', 'NU' => '683', 'NZ' => '64', 'OM' => '968',
        'PA' => '507', 'PE' => '51', 'PF' => '689', 'PG' => '675', 'PH' => '63', 'PK' => '92', 'PL' => '48',
        'PM' => '508', 'PR' => '1', 'PS' => '970', 'PT' => '351', 'PW' => '680', 'PY' => '595', 'QA' => '974',
        'RE' => '262', 'RO' => '40', 'RS' => '381', 'RU' => '7', 'RW' => '250', 'SA' => '966', 'SB' => '677',
        'SC' => '248', 'SD' => '249', 'SE' => '46', 'SG' => '65', 'SH' => '290', 'SI' => '386', 'SJ' => '47',
        'SK' => '421', 'SL' => '232', 'SM' => '378', 'SN' => '221', 'SO' => '252', 'SR' => '597', 'SS' => '211',
        'ST' => '239', 'SV' => '503', 'SX' => '1', 'SY' => '963', 'SZ' => '268', 'TC' => '1', 'TD' => '235',
        'TG' => '228', 'TH' => '66', 'TJ' => '992', 'TK' => '690', 'TL' => '670', 'TM' => '993', 'TN' => '216',
        'TO' => '676', 'TR' => '90', 'TT' => '1', 'TV' => '688', 'TW' => '886', 'TZ' => '255', 'UA' => '380',
        'UG' => '256', 'US' => '1', 'UY' => '598', 'UZ' => '998', 'VA' => '39', 'VC' => '1', 'VE' => '58',
        'VG' => '1', 'VI' => '1', 'VN' => '84', 'VU' => '678', 'WF' => '681', 'WS' => '685', 'XK' => '383',
        'YE' => '967', 'YT' => '262', 'ZA' => '27', 'ZM' => '260', 'ZW' => '263',
    ];

    /**
     * Country picked back from a shared calling code when no hint matches (PHP casts the keys to int).
     *
     * @var array<int, string>
     */
    private const PRIMARY_COUNTRIES = [
        '1' => 'US', '7' => 'RU', '39' => 'IT', '44' => 'GB', '47' => 'NO', '61' => 'AU', '212' => 'MA',
        '262' => 'RE', '290' => 'SH', '358' => 'FI', '590' => 'GP', '599' => 'CW',
    ];

    /**
     * National number lengths (trunk prefix excluded); other countries fall back to E.164 bounds.
     *
     * @var array<string, array{int, int}>
     */
    private const NATIONAL_LENGTHS = [
        'AT' => [4, 13], 'AU' => [9, 9], 'BE' => [8, 9], 'CA' => [10, 10], 'CH' => [9, 9], 'CZ' => [9, 9],
        'DE' => [5, 13], 'DK' => [8, 8], 'ES' => [9, 9], 'FR' => [9, 9], 'GB' => [9, 10], 'IE' => [7, 9],
        'IT' => [6, 11], 'LU' => [4, 11], 'MC' => [8, 9], 'NL' => [9, 9], 'NO' => [8, 8], 'PL' => [9, 9],
        'PT' => [9, 9], 'SE' => [7, 10], 'SK' => [9, 9], 'US' => [10, 10],
    ];

    /** Countries where the leading 0 belongs to the number itself and is kept after the calling code */
    private const KEEP_LEADING_ZERO = ['CI', 'IT', 'SM', 'VA'];

    public static function exists(string $country): bool
    {
        return isset(self::CODES[$country]);
    }

    public static function getCode(string $country): string
    {
        return self::CODES[$country] ?? throw new \InvalidArgumentException(sprintf('Unknown country "%s".', $country));
    }

    /**
     * @return array{int, int} [min, max] digits after the calling code
     */
    public static function getNationalLengths(string $country): array
    {
        return self::NATIONAL_LENGTHS[$country]
            ?? [self::MIN_NATIONAL_DIGITS, self::MAX_DIGITS - strlen(self::getCode($country))];
    }

    public static function keepsLeadingZero(string $country): bool
    {
        return in_array($country, self::KEEP_LEADING_ZERO, true);
    }

    /**
     * Splits international digits ("41791234567") into [country, national number].
     * Calling codes are prefix-free, so the first match is the only one.
     *
     * @return array{string, string}|null
     */
    public static function split(string $digits, ?string $preferredCountry = null): ?array
    {
        for ($length = 1; $length <= 3; $length++) {
            $code = substr($digits, 0, $length);
            $countries = array_keys(self::CODES, $code, true);
            if (!$countries) {
                continue;
            }

            if ($preferredCountry !== null && in_array($preferredCountry, $countries, true)) {
                $country = $preferredCountry;
            } else {
                $country = self::PRIMARY_COUNTRIES[(int)$code] ?? $countries[0];
            }

            return [$country, substr($digits, $length)];
        }

        return null;
    }
}
