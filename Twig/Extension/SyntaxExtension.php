<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Twig\Extension;

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
        return array(
            'format_sql'    => new \Twig_Filter_Method($this, 'formatSQL', array('is_safe' => array('html'))),
        );
    }

    public function getName()
    {
        return 'propel_syntax_extension';
    }

    public function formatSQL($sql)
    {
        $sql = preg_replace('/\b(UPDATE|SET|SELECT|FROM|AS|LIMIT|ASC|COUNT|DESC|WHERE|LEFT JOIN|INNER JOIN|RIGHT JOIN|ORDER BY|GROUP BY|IN|LIKE|DISTINCT|DELETE|INSERT|INTO|VALUES|ON|AND|OR)\b/', '<span class="SQLKeyword">\\1</span>', $sql);

        $sql = preg_replace('/\b(FROM|WHERE|INNER JOIN|LEFT JOIN|RIGHT JOIN|ORDER BY|GROUP BY)\b/', '<br />\\1', $sql);

        return $sql;
    }
}
