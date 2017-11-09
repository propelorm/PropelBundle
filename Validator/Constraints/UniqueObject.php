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
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Constraint for the Unique Object validator
 *
 * @author Maxime AILLOUD <maxime.ailloud@gmail.com>
 * @author Marek Kalnik <marekk@theodo.fr>
 */
class UniqueObject extends Constraint
{
    /**
     * @var string
     */
    public $message = 'A {{ object_class }} object already exists with {{ fields }}';

    /**
     * @var string Used to merge multiple fields in the message
     */
    public $messageFieldSeparator = ' and ';

    /**
     * @var array
     */
    public $fields = array();

    /**
     * @var string Used to set the path where the error will be attached, default is global.
     */
    public $errorPath;

    public function __construct($options = null)
    {
        parent::__construct($options);

        if (!is_array($this->fields) && !is_string($this->fields)) {
            throw new UnexpectedTypeException($this->fields, 'array');
        }

        if (0 === count($this->fields)) {
            throw new ConstraintDefinitionException("At least one field must be specified.");
        }

        if (null !== $this->errorPath && !is_string($this->errorPath)) {
            throw new UnexpectedTypeException($this->errorPath, 'string or null');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getRequiredOptions()
    {
        return array('fields');
    }

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
