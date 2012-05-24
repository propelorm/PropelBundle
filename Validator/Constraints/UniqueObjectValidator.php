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
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Unique Object Validator checks if one or a set of fields contain unique values.
 *
 * @author Maxime AILLOUD <maxime.ailloud@gmail.com>
 */
class UniqueObjectValidator extends ConstraintValidator
{
    /**
     * @param object                                  $object
     * @param \Symfony\Component\Validator\Constraint $constraint
     * @return Boolean
     */
    public function isValid($object, Constraint $constraint)
    {
        if (!is_array($constraint->fields) && !is_string($constraint->fields)) {
            throw new UnexpectedTypeException($constraint->fields, 'array');
        }

        $fields = (array)$constraint->fields;

        if (0 === count($fields)) {
            throw new ConstraintDefinitionException("At least one field must be specified.");
        }

        $class = get_class($object);
        $peerClass = $class . 'Peer';
        $queryClass = $class . 'Query';
        $classFields = $peerClass::getFieldNames(\BasePeer::TYPE_FIELDNAME);

        foreach ($fields as $fieldName) {
            if (false === array_search($fieldName, $classFields)) {
                throw new ConstraintDefinitionException('The field "' . $fieldName .'" doesn\'t exist in the "' . $class . '" class.');
            }
        }

        $bddUsersQuery = $queryClass::create();
        foreach ($fields as $fieldName) {
            $bddUsersQuery->filterBy($peerClass::translateFieldName($fieldName, \BasePeer::TYPE_FIELDNAME, \BasePeer::TYPE_PHPNAME), $object->getByName($fieldName, \BasePeer::TYPE_FIELDNAME));
        }
        $bddUsers = $bddUsersQuery->find();

        $countUser = count($bddUsers);

        if ($countUser > 1 || ($countUser === 1 && $object !== $bddUsers[0])) {
            $constraintMessage = $constraint->getMessage();
            $constraintMessage .= ' with';

            foreach ($fields as $fieldName) {
              $constraintMessage .= sprintf(' %s "%s" and', $peerClass::translateFieldName($fieldName, \BasePeer::TYPE_FIELDNAME, \BasePeer::TYPE_PHPNAME), $object->getByName($fieldName, \BasePeer::TYPE_FIELDNAME));
            }

            $constraintMessage = substr($constraintMessage, 0, -4) . '.';
            $this->setMessage($constraintMessage);

            return false;
        }

        return true;
    }
}
