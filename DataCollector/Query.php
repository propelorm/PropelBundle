<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\DataCollector;

/**
 * A Query class is designed to represent query information.
 *
 * @author William DURAND <william.durand1@gmail.com>
 */
class Query
{
    /**
     * SQL statement
     * @var string
     */
    private $sql;
    /**
     * Execution time
     *
     * @var string
     */
    private $time;
    /**
     * Memory
     *
     * @var string
     */
    private $memory;

    /**
     * Default constructor
     *
     * @param $sql  A SQL statement
     * @param $time An execution time
     * @param $memory   Memory used
     */
    public function __construct($sql, $time, $memory)
    {
        $this->sql      = $sql;
        $this->time     = $time;
        $this->memory   = $memory;
    }

    /**
     * Getter
     *
     * @return string   SQL statement
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     * Getter
     *
     * @return string   Execution time
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Getter
     *
     * @return string   Memory
     */
    public function getMemory()
    {
        return $this->memory;
    }
}
