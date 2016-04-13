<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Util;

/**
 * The Propel inflector class provides methods for inflecting text.
 *
 * @author William Durand <william.durand1@gmail.com>
 */
class PropelInflector
{
    /**
     * Camelize a word.
     * Inspirated by https://github.com/doctrine/common/blob/master/lib/Doctrine/Common/Util/Inflector.php
     *
     * @param  string $word The word to camelize.
     * @return string
     */
    public static function camelize($word)
    {
        return lcfirst(str_replace(" ", "", ucwords(strtr($word, "_-", "  "))));
    }
}
