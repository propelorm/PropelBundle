<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\DependencyInjection;

/**
 * Properties.
 *
 * @author William Durand <william.durand1@gmail.com>
 */
class Properties
{
    /**
     * Build properties.
     *
     * @var array
     */
    private $properties;

    /**
     * Default constructor.
     *
     * @param $properties   An array of properties.
     */
    public function __construct(array $properties = array())
    {
        $this->properties = $properties;
    }

    /**
     * Get properties.
     *
     * @return array An array of properties.
     */
    public function getProperties()
    {
        return $this->properties;
    }
}
