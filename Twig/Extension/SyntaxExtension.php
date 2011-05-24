<?php

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
            'formatSQL'    => new \Twig_Filter_Method($this, 'formatSQL', array('is_safe' => array('html'))),
        );
    }

    public function getName()
    {
        return 'propel_syntax_extension';
    }

    public function formatSQL($sql)
    {
        return preg_replace('/\b(UPDATE|SET|SELECT|FROM|AS|LIMIT|ASC|COUNT|DESC|WHERE|LEFT JOIN|INNER JOIN|RIGHT JOIN|ORDER BY|GROUP BY|IN|LIKE|DISTINCT|DELETE|INSERT|INTO|VALUES)\b/', '<span class="SQLKeyword">\\1</span>', $sql);
    }
}
