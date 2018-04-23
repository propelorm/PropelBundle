<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Bundle\PropelBundle\Form;

use Propel\Bundle\PropelBundle\Form\Type\ModelType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
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
     * {@inheritdoc}
     */
    public function guessType($class, $property)
    {
        if (!$table = $this->getTable($class)) {
            return new TypeGuess(TextType::class, array(), Guess::LOW_CONFIDENCE);
        }

        foreach ($table->getRelations() as $relation) {
            if ($relation->getType() === \RelationMap::MANY_TO_ONE) {
                if (strtolower($property) === strtolower($relation->getName())) {
                    return new TypeGuess(ModelType::class, array(
                        'class' => $relation->getForeignTable()->getClassName(),
                        'multiple' => false,
                    ), Guess::HIGH_CONFIDENCE);
                }
            } elseif ($relation->getType() === \RelationMap::ONE_TO_MANY) {
                if (strtolower($property) === strtolower($relation->getPluralName())) {
                    return new TypeGuess(ModelType::class, array(
                        'class' => $relation->getForeignTable()->getClassName(),
                        'multiple' => true,
                    ), Guess::HIGH_CONFIDENCE);
                }
            } elseif ($relation->getType() === \RelationMap::MANY_TO_MANY) {
                if (strtolower($property) == strtolower($relation->getPluralName())) {
                    return new TypeGuess(ModelType::class, array(
                        'class' => $relation->getLocalTable()->getClassName(),
                        'multiple' => true,
                    ), Guess::HIGH_CONFIDENCE);
                }
            }
        }

        if (!$column = $this->getColumn($class, $property)) {
            return new TypeGuess(TextType::class, array(), Guess::LOW_CONFIDENCE);
        }

        switch ($column->getType()) {
            case \PropelColumnTypes::BOOLEAN:
            case \PropelColumnTypes::BOOLEAN_EMU:
                return new TypeGuess(CheckboxType::class, array(), Guess::HIGH_CONFIDENCE);
            case \PropelColumnTypes::TIMESTAMP:
            case \PropelColumnTypes::BU_TIMESTAMP:
                return new TypeGuess(DateTimeType::class, array(), Guess::HIGH_CONFIDENCE);
            case \PropelColumnTypes::DATE:
            case \PropelColumnTypes::BU_DATE:
                return new TypeGuess(DateType::class, array(), Guess::HIGH_CONFIDENCE);
            case \PropelColumnTypes::TIME:
                return new TypeGuess(TimeType::class, array(), Guess::HIGH_CONFIDENCE);
            case \PropelColumnTypes::FLOAT:
            case \PropelColumnTypes::REAL:
            case \PropelColumnTypes::DOUBLE:
            case \PropelColumnTypes::DECIMAL:
                return new TypeGuess(NumberType::class, array(), Guess::MEDIUM_CONFIDENCE);
            case \PropelColumnTypes::TINYINT:
            case \PropelColumnTypes::SMALLINT:
            case \PropelColumnTypes::INTEGER:
            case \PropelColumnTypes::BIGINT:
            case \PropelColumnTypes::NUMERIC:
                return new TypeGuess(IntegerType::class, array(), Guess::MEDIUM_CONFIDENCE);
            case \PropelColumnTypes::ENUM:
            case \PropelColumnTypes::CHAR:
                if ($column->getValueSet()) {
                    //check if this is mysql enum
                    $choices = $column->getValueSet();
                    $labels = array_map('ucfirst', $choices);

                    return new TypeGuess(ChoiceType::class, array('choices' => array_combine($choices, $labels)), Guess::MEDIUM_CONFIDENCE);
                }
            case \PropelColumnTypes::VARCHAR:
                return new TypeGuess(TextType::class, array(), Guess::MEDIUM_CONFIDENCE);
            case \PropelColumnTypes::LONGVARCHAR:
            case \PropelColumnTypes::BLOB:
            case \PropelColumnTypes::CLOB:
            case \PropelColumnTypes::CLOB_EMU:
                return new TypeGuess(TextareaType::class, array(), Guess::MEDIUM_CONFIDENCE);
            default:
                return new TypeGuess(TextType::class, array(), Guess::LOW_CONFIDENCE);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function guessRequired($class, $property)
    {
        if ($column = $this->getColumn($class, $property)) {
            return new ValueGuess($column->isNotNull(), Guess::HIGH_CONFIDENCE);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function guessMaxLength($class, $property)
    {
        if ($column = $this->getColumn($class, $property)) {
            if ($column->isText()) {
                return new ValueGuess($column->getSize(), Guess::HIGH_CONFIDENCE);
            }
            switch ($column->getType()) {
                case \PropelColumnTypes::FLOAT:
                case \PropelColumnTypes::REAL:
                case \PropelColumnTypes::DOUBLE:
                case \PropelColumnTypes::DECIMAL:
                    return new ValueGuess(null, Guess::MEDIUM_CONFIDENCE);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function guessPattern($class, $property)
    {
        if ($column = $this->getColumn($class, $property)) {
            switch ($column->getType()) {
                case \PropelColumnTypes::FLOAT:
                case \PropelColumnTypes::REAL:
                case \PropelColumnTypes::DOUBLE:
                case \PropelColumnTypes::DECIMAL:
                    return new ValueGuess(null, Guess::MEDIUM_CONFIDENCE);
            }
        }
    }

    /**
     * @param string $class
     *
     * @return \TableMap
     */
    protected function getTable($class)
    {
        if (array_key_exists($class, $this->cache)) {
            return $this->cache[$class];
        }

        if (class_exists($queryClass = $class.'Query')) {
            /** @var \ModelCriteria $query */
            $query = new $queryClass();

            return $this->cache[$class] = $query->getTableMap();
        }
    }

    /**
     * @param string $class
     * @param string $property
     *
     * @return \ColumnMap
     * @throws \PropelException
     */
    protected function getColumn($class, $property)
    {
        if (array_key_exists($class.'::'.$property, $this->cache)) {
            return $this->cache[$class.'::'.$property];
        }

        $table = $this->getTable($class);

        if ($table && $table->hasColumn($property)) {
            return $this->cache[$class.'::'.$property] = $table->getColumn($property);
        }

        if ($table && $table->hasColumnByInsensitiveCase($property)) {
            return $this->cache[$class.'::'.$property] = $table->getColumnByInsensitiveCase($property);
        }
    }
}
