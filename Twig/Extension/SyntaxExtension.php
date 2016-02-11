<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Twig\Extension;

/**
 * SyntaxExtension class
 *
 * @package PropelBundle
 * @subpackage Extension
 * @author William DURAND <william.durand1@gmail.com>
 */
class SyntaxExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('format_sql', [$this, 'formatSQL'], ['is_safe' => ['html']]),
            new \Twig_SimpleFilter('format_memory', [$this, 'formatMemory']),
        ];
    }

    public function getName()
    {
        return 'propel_syntax_extension';
    }

    /**
     * Format a byte count into a human-readable representation.
     *
     * @param integer $bytes     Byte count to convert. Can be negative.
     * @param integer $precision How many decimals to include.
     *
     * @return string
     */
    public function formatMemory($bytes, $precision = 3)
    {
        $absBytes = abs($bytes);
        $sign = ($bytes == $absBytes) ? 1 : -1;
        $suffix = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $total = count($suffix);

        for ($i = 0; $absBytes > 1024 && $i < $total; $i++) {
            $absBytes /= 1024;
        }

        return self::toPrecision($sign * $absBytes, $precision) . $suffix[$i];
    }

    public function formatSQL($sql)
    {
        // list of keywords to prepend a newline in output
        $newlines = array(
            'FROM',
            '(((FULL|LEFT|RIGHT)? ?(OUTER|INNER)?|CROSS|NATURAL)? JOIN)',
            'VALUES',
            'WHERE',
            'ORDER BY',
            'GROUP BY',
            'HAVING',
            'LIMIT',
        );

        // list of keywords to highlight
        $keywords = array_merge($newlines, array(
            // base
            'SELECT', 'UPDATE', 'DELETE', 'INSERT', 'REPLACE',
            'SET',
            'INTO',
            'AS',
            'DISTINCT',

            // most used methods
            'COUNT',
            'AVG',
            'MIN',
            'MAX',

            // joins
            'ON', 'USING',

            // where clause
            '(IS (NOT)?)?NULL',
            '(NOT )?IN',
            '(NOT )?I?LIKE',
            'AND', 'OR', 'XOR',
            'BETWEEN',

            // order, group, limit ..
            'ASC',
            'DESC',
            'OFFSET',
        ));

        $sql = preg_replace(array(
            '/\b('.implode('|', $newlines).')\b/',
            '/\b('.implode('|', $keywords).')\b/',
            '/(\/\*.*\*\/)/',
            '/(`[^`.]*`)/',
            '/(([0-9a-zA-Z$_]+)\.([0-9a-zA-Z$_]+))/',
        ), array(
            '<br />\\1',
            '<span class="SQLKeyword">\\1</span>',
            '<span class="SQLComment">\\1</span>',
            '<span class="SQLName">\\1</span>',
            '<span class="SQLName">\\1</span>',
        ), $sql);

        return $sql;
    }

    /**
     * Rounding to significant digits (sort of like JavaScript's toPrecision()).
     *
     * @param float   $number             Value to round
     * @param integer $significantFigures Number of significant figures
     *
     * @return float
     */
    public static function toPrecision($number, $significantFigures = 3)
    {
        if (0 === $number) {
            return 0;
        }

        $significantDecimals = floor($significantFigures - log10(abs($number)));
        $magnitude = pow(10, $significantDecimals);
        $shifted = round($number * $magnitude);

        return number_format($shifted / $magnitude, $significantDecimals);
    }
}
