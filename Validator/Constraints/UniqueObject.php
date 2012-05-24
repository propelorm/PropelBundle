<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint for the Unique Object validator
 *
 * @author Maxime AILLOUD <maxime.ailloud@gmail.com>
 */
class UniqueObject extends Constraint
{
    /**
     * @var array
     */
    public $fields = array();

    /**
     * @return array
     */
    public function getRequiredOptions()
    {
        return array('fields');
    }

    /**
     * The validator must be defined as a service with this name.
     *
     * @return string
     */
    public function validatedBy()
    {
        return get_class($this).'Validator';
    }

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    /**
     * @return string
     */
    public function getDefaultOption()
    {
        return 'fields';
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return 'A ' . $this->groups[1] . ' object already exists';
    }
}
