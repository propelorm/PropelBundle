<?php

namespace Propel\PropelBundle\Form;

use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\Guess\Guess;
use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\Form\Guess\ValueGuess;

/**
 * Propel Type guesser.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class PropelTypeGuesser implements FormTypeGuesserInterface
{
    private $cache = array();

    /**
     * {@inheritDoc}
     */
    public function guessType($class, $property)
    {
        if (!$table = $this->getTable($class)) {
            return new TypeGuess('text', array(), Guess::LOW_CONFIDENCE);
        }

        foreach ($table->getRelations() as $relation) {
            if (in_array($relation->getType(), array(\RelationMap::MANY_TO_ONE, \RelationMap::ONE_TO_MANY))) {
                if ($property == $relation->getForeignTable()->getName()) {
                    return new TypeGuess('model', array(
                        'class'    => $relation->getForeignTable()->getClassName(),
                        'multiple' => \RelationMap::MANY_TO_ONE === $relation->getType() ? false : true,
                    ), Guess::HIGH_CONFIDENCE);
                }
            }
        }

        if (!$column = $this->getColumn($class, $property)) {
            return new TypeGuess('text', array(), Guess::LOW_CONFIDENCE);
        }

        switch ($column->getType()) {
            case \PropelColumnTypes::BOOLEAN:
            case \PropelColumnTypes::BOOLEAN_EMU:
                return new TypeGuess('checkbox', array(), Guess::HIGH_CONFIDENCE);
            case \PropelColumnTypes::TIMESTAMP:
            case \PropelColumnTypes::BU_TIMESTAMP:
                return new TypeGuess('datetime', array(), Guess::HIGH_CONFIDENCE);
            case \PropelColumnTypes::DATE:
            case \PropelColumnTypes::BU_DATE:
                return new TypeGuess('date', array(), Guess::HIGH_CONFIDENCE);
            case \PropelColumnTypes::TIME:
                return new TypeGuess('time', array(), Guess::HIGH_CONFIDENCE);
            case \PropelColumnTypes::FLOAT:
            case \PropelColumnTypes::REAL:
            case \PropelColumnTypes::DOUBLE:
            case \PropelColumnTypes::DECIMAL:
                return new TypeGuess('number', array(), Guess::MEDIUM_CONFIDENCE);
            case \PropelColumnTypes::TINYINT:
            case \PropelColumnTypes::SMALLINT:
            case \PropelColumnTypes::INTEGER:
            case \PropelColumnTypes::BIGINT:
            case \PropelColumnTypes::NUMERIC:
                return new TypeGuess('integer', array(), Guess::MEDIUM_CONFIDENCE);
            case \PropelColumnTypes::CHAR:
            case \PropelColumnTypes::VARCHAR:
                return new TypeGuess('text', array(), Guess::MEDIUM_CONFIDENCE);
            case \PropelColumnTypes::LONGVARCHAR:
            case \PropelColumnTypes::BLOB:
            case \PropelColumnTypes::CLOB:
            case \PropelColumnTypes::CLOB_EMU:
                return new TypeGuess('textarea', array(), Guess::MEDIUM_CONFIDENCE);
            default:
                return new TypeGuess('text', array(), Guess::LOW_CONFIDENCE);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function guessRequired($class, $property)
    {
        if ($column = $this->getColumn($class, $property)) {
            return new ValueGuess($column->isNotNull(), Guess::HIGH_CONFIDENCE);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function guessMaxLength($class, $property)
    {
        if (($column = $this->getColumn($class, $property)) && $column->isText()) {
            return new ValueGuess($column->getSize(), Guess::HIGH_CONFIDENCE);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function guessMinLength($class, $property)
    {
    }

    protected function getTable($class)
    {
        if (isset($this->cache[$class])) {
            return $this->cache[$class];
        }

        if (class_exists($queryClass = $class.'Query')) {
            $query = new $queryClass();

            return $this->cache[$class] = $query->getTableMap();
        }
    }

    protected function getColumn($class, $property)
    {
        if (isset($this->cache[$class.'::'.$property])) {
            return $this->cache[$class.'::'.$property];
        }

        $table = $this->getTable($class);

        if ($table && $table->hasColumn($property)) {
            return $this->cache[$class.'::'.$property] = $table->getColumn($property);
        }
    }
}
