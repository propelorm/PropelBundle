<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\DataFixtures\Dumper;

/**
 * Interface that exposes how Propel data dumpers should work.
 *
 * @author William Durand <william.durand1@gmail.com>
 */
interface DataDumperInterface
{
    /**
     * Dumps data to fixtures from a given connection.
     *
     * @param string $filename       The file name to write data.
     * @param string $connectionName The Propel connection name.
     */
    public function dump($filename, $connectionName = null);
}
