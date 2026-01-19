<?php

if (!function_exists('formatNumber')) {
    /**
     * Format number with Indonesian locale (dot for thousands, comma for decimals)
     * 
     * @param mixed $number
     * @param int $decimals
     * @return string
     */
    function formatNumber($number, int $decimals = 0): string
    {
        if ($number === null || $number === '') {
            return '0';
        }

        // Indonesian format: 1.000.000,50
        return number_format((float) $number, $decimals, ',', '.');
    }
}
