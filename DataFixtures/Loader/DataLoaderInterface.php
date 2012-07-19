<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\DataFixtures\Loader;

/**
 * Interface that exposes how Propel data loaders should work.
 *
 * @author William Durand <william.durand1@gmail.com>
 */
interface DataLoaderInterface
{
    /**
     * Loads data from a set of files.
     *
     * @param array  $files          A set of files containing datas to load.
     * @param string $connectionName The Propel connection name
     */
    public function load($files = array(), $connectionName);
}
