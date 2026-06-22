<?php

namespace App\Helpers;

class AmountInWords
{
    public static function convert(float $number, ?string $currency = null): string
    {
        $currency ??= currentBusiness()?->currency ?? 'UGX';
        $no = round($number, 2);
        $decimal = sprintf('%02d', ($no - floor($no)) * 100);
        $whole = floor($no);

        $words = self::numberToWords($whole);

        $result = ucfirst($words) . ' ' . $currency;
        if ((int) $decimal > 0) {
            $result .= ' and ' . $decimal . '/100';
        } else {
            $result .= ' only';
        }

        return $result;
    }

    private static function numberToWords(int $number): string
    {
        $ones = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten',
            'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'];
        $tens = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];

        if ($number == 0) return 'zero';

        $words = '';

        if ($number >= 1000000) {
            $millions = floor($number / 1000000);
            $words .= self::numberToWords($millions) . ' million ';
            $number %= 1000000;
        }

        if ($number >= 1000) {
            $thousands = floor($number / 1000);
            $words .= self::numberToWords($thousands) . ' thousand ';
            $number %= 1000;
        }

        if ($number >= 100) {
            $hundreds = floor($number / 100);
            $words .= $ones[$hundreds] . ' hundred ';
            $number %= 100;
        }

        if ($number > 0) {
            if ($words !== '') $words .= 'and ';
            if ($number < 20) {
                $words .= $ones[$number];
            } else {
                $words .= $tens[floor($number / 10)];
                if ($number % 10 > 0) {
                    $words .= '-' . $ones[$number % 10];
                }
            }
        }

        return trim($words);
    }
}
