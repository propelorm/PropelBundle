<?php

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
    public function __construct(array $properties) {
        $this->properties = $properties;
    }

    /**
     * Get properties.
     *
     * @return array   An array of properties.
     */
    public function getProperties() {
        return $this->properties;
    }
}
