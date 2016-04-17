<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Validator\Constraints;

use Propel\Runtime\Map\TableMap;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

/**
 * Unique Object Validator checks if one or a set of fields contain unique values.
 *
 * @author Maxime AILLOUD <maxime.ailloud@gmail.com>
 * @author Marek Kalnik <marekk@theodo.fr>
 */
class UniqueObjectValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($object, Constraint $constraint)
    {
        $fields         = (array) $constraint->fields;
        $class          = get_class($object);
        $queryClass     = $class . 'Query';
        $tableMapClass  = $class::TABLE_MAP;
        $classFields    = $tableMapClass::getFieldnames(TableMap::TYPE_FIELDNAME);

        foreach ($fields as $fieldName) {
            if (false === array_search($fieldName, $classFields)) {
                throw new ConstraintDefinitionException('The field "' . $fieldName .'" doesn\'t exist in the "' . $class . '" class.');
            }
        }

        $bddUsersQuery = $queryClass::create();
        foreach ($fields as $fieldName) {
            $bddUsersQuery->filterBy(
                $tableMapClass::translateFieldname($fieldName, TableMap::TYPE_FIELDNAME, TableMap::TYPE_PHPNAME),
                $object->getByName($fieldName, TableMap::TYPE_FIELDNAME)
            );
        }

        $bddUsers  = $bddUsersQuery->find();
        $countUser = count($bddUsers);

        if ($countUser > 1 || ($countUser === 1 && !$object->equals($bddUsers[0]))) {
            $fieldParts = array();

            foreach ($fields as $fieldName) {
                $fieldParts[] = sprintf(
                    '%s "%s"',
                    $tableMapClass::translateFieldname($fieldName, TableMap::TYPE_FIELDNAME, TableMap::TYPE_PHPNAME),
                    $object->getByName($fieldName, TableMap::TYPE_FIELDNAME)
                );
            }

            $this->context->buildViolation($constraint->message)
                ->atPath($constraint->errorPath)
                ->setParameters(array(
                    '{{ object_class }}' => $class,
                    '{{ fields }}' => implode($constraint->messageFieldSeparator, $fieldParts)
                ))
                ->addViolation();

        }
    }
}
