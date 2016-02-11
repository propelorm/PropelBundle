<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Bundle\PropelBundle\Tests\Form;

use Propel\Bundle\PropelBundle\Form\Type\ModelType;
use Propel\Bundle\PropelBundle\Form\TypeGuesser;
use Propel\Bundle\PropelBundle\Tests\TestCase;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Guess\Guess;

class TypeGuesserTest extends TestCase
{
    const CLASS_NAME = 'Propel\Bundle\PropelBundle\Tests\Fixtures\Item';

    const UNKNOWN_CLASS_NAME = 'Propel\Bundle\PropelBundle\Tests\Fixtures\UnknownItem';

    private $guesser;

    public function setUp()
    {
        $this->guesser = new TypeGuesser();
    }

    public function testGuessMaxLengthWithText()
    {
        $value = $this->guesser->guessMaxLength(self::CLASS_NAME, 'value');

        $this->assertNotNull($value);
        $this->assertEquals(255, $value->getValue());
    }

    public function testGuessMaxLengthWithFloat()
    {
        $value = $this->guesser->guessMaxLength(self::CLASS_NAME, 'price');

        $this->assertNotNull($value);
        $this->assertNull($value->getValue());
    }

    public function testGuessMinLengthWithText()
    {
        $value = $this->guesser->guessPattern(self::CLASS_NAME, 'value');

        $this->assertNull($value);
    }

    public function testGuessMinLengthWithFloat()
    {
        $value = $this->guesser->guessPattern(self::CLASS_NAME, 'price');

        $this->assertNotNull($value);
        $this->assertNull($value->getValue());
    }

    public function testGuessRequired()
    {
        $value = $this->guesser->guessRequired(self::CLASS_NAME, 'id');

        $this->assertNotNull($value);
        $this->assertTrue($value->getValue());
    }

    public function testGuessRequiredWithNullableColumn()
    {
        $value = $this->guesser->guessRequired(self::CLASS_NAME, 'value');

        $this->assertNotNull($value);
        $this->assertFalse($value->getValue());
    }

    public function testGuessTypeWithoutTable()
    {
        $value = $this->guesser->guessType(self::UNKNOWN_CLASS_NAME, 'property');

        $this->assertNotNull($value);
        $this->assertEquals(TextType::class, $value->getType());
        $this->assertEquals(Guess::LOW_CONFIDENCE, $value->getConfidence());
    }

    public function testGuessTypeWithoutColumn()
    {
        $value = $this->guesser->guessType(self::CLASS_NAME, 'property');

        $this->assertNotNull($value);
        $this->assertEquals(TextType::class, $value->getType());
        $this->assertEquals(Guess::LOW_CONFIDENCE, $value->getConfidence());
    }

    /**
     * @dataProvider dataProviderForGuessType
     */
    public function testGuessType($property, $type, $confidence, $multiple = null)
    {
        $value = $this->guesser->guessType(self::CLASS_NAME, $property);

        $this->assertNotNull($value);
        $this->assertEquals($type, $value->getType());
        $this->assertEquals($confidence, $value->getConfidence());

        if ($type === ModelType::class) {
            $options = $value->getOptions();

            $this->assertSame($multiple, $options['multiple']);
        }
    }

    public static function dataProviderForGuessType()
    {
        return array(
            array('is_active',  CheckboxType::class, Guess::HIGH_CONFIDENCE),
            array('enabled',    CheckboxType::class, Guess::HIGH_CONFIDENCE),
            array('id',         IntegerType::class,  Guess::MEDIUM_CONFIDENCE),
            array('value',      TextType::class,     Guess::MEDIUM_CONFIDENCE),
            array('price',      NumberType::class,   Guess::MEDIUM_CONFIDENCE),
            array('updated_at', DateTimeType::class, Guess::HIGH_CONFIDENCE),

            array('Authors',    ModelType::class,    Guess::HIGH_CONFIDENCE,     true),
            array('Resellers',  ModelType::class,    Guess::HIGH_CONFIDENCE,     true),
            array('MainAuthor', ModelType::class,    Guess::HIGH_CONFIDENCE,     false),
        );
    }
}
