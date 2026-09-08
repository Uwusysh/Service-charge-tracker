<?php

namespace App\Support;

class Money
{
    public static function formatGbp(null|int|float|string $amount): string
    {
        if ($amount === null || $amount === '') {
            return '£0.00';
        }

        $value = self::normalize($amount);

        return '£'.number_format((float) $value, 2, '.', ',');
    }

    public static function normalize(null|int|float|string $amount): string
    {
        if ($amount === null || $amount === '') {
            return '0.00';
        }

        return number_format((float) $amount, 2, '.', '');
    }

    public static function add(string ...$amounts): string
    {
        $total = '0.00';
        foreach ($amounts as $amount) {
            $total = bcadd($total, self::normalize($amount), 2);
        }

        return $total;
    }

    public static function subtract(string $left, string $right): string
    {
        return bcsub(self::normalize($left), self::normalize($right), 2);
    }

    public static function multiply(string $amount, string $factor, int $scale = 2): string
    {
        return bcmul(self::normalize($amount), $factor, $scale);
    }

    public static function roundPenny(string $amount): string
    {
        $scaled = bcmul($amount, '100', 8);
        $rounded = (string) (int) round((float) $scaled);

        return bcdiv($rounded, '100', 2);
    }

    public static function percentOf(string $amount, string $percentage): string
    {
        $share = bcmul(self::normalize($amount), bcdiv($percentage, '100', 12), 8);

        return self::roundPenny($share);
    }

    public static function compare(string $left, string $right): int
    {
        return bccomp(self::normalize($left), self::normalize($right), 2);
    }
}
