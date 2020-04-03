<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */
namespace Propel\Bundle\PropelBundle\Tests\Form\Type;

use Propel\Bundle\PropelBundle\Form\PropelExtension;
use Propel\Bundle\PropelBundle\Tests\Fixtures\Model\Book;
use Propel\Bundle\PropelBundle\Tests\Fixtures\Model\BookQuery;
use Propel\Bundle\PropelBundle\Tests\Fixtures\Model\map\BookTableMap;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * @covers \Propel\Bundle\PropelBundle\Form\Type\ModelType
 */
class ModelTypeTest extends TypeTestCase
{
    const TESTED_TYPE = 'Propel\Bundle\PropelBundle\Form\Type\ModelType';

    /**
     * @var FormFactoryInterface
     */
    protected $factory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\ModelCriteria
     */
    protected $query;

    /**
     * @var Book[]
     */
    protected $choices;


    protected function setUp()
    {
        parent::setUp();

        $this->query = $this->getMockBuilder('\Propel\Bundle\PropelBundle\Tests\Fixtures\Model\BookQuery')
            ->disableOriginalConstructor()
            ->setMethods(array('getTableMap', 'find'))
            ->getMock();

        $this->query->expects($this->any())
            ->method('getTableMap')
            ->willReturn(new BookTableMap());

        $this->choices = array(
            'Bernhard' => (new Book())->setId(1)->setName('Bernhard'),
            'Fabien' => (new Book())->setId(2)->setName('Fabien'),
            'Kris' => (new Book())->setId(3)->setName('Kris'),
            'Jon' => (new Book())->setId(4)->setName('Jon'),
            'Roman' => (new Book())->setId(5)->setName('Roman'),
        );
    }

    protected function getExtensions()
    {
        return array(new PropelExtension());
    }

    /**
     * @expectedException Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     */
    public function testEmptyClassExpectations()
    {
        $form = $this->factory->create(
            static::TESTED_TYPE,
            null,
            array(
                'class' => null,
            )
        );
    }

    /**
     * @expectedException Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     */
    public function testInvalidClassTypeExpectations()
    {
        $form = $this->factory->create(
            static::TESTED_TYPE,
            null,
            array(
                'class' => '\stdClass',
            )
        );
    }

    public function testChoiceListAndChoicesCanBeEmpty()
    {
        $this->query->expects($this->any())
            ->method('getTableMap')
            ->willReturn(new BookTableMap());

        $this->query->expects($this->any())
            ->method('find')
            ->willReturn(new \PropelObjectCollection());

        $form = $this->factory->create(
            static::TESTED_TYPE,
            null,
            array(
                'query' => $this->query,
                'class' => BookQuery::class,
            )
        );

        static::assertInstanceOf('Symfony\Component\Form\FormInterface', $form);
    }

    public function ChoiceListTestMultiple()
    {
        $form = $this->factory->create(
            static::TESTED_TYPE,
            null,
            array(
                'query' => $this->query,
                'class' => BookQuery::class,
                'multiple' => true,
                'expanded' => false,
            )
        );
    }

    public function testExpandedCheckboxesAreNeverRequired()
    {
        $form = $this->factory->create(
            static::TESTED_TYPE,
            null,
            array(
                'class' => Book::class,
                'multiple' => true,
                'expanded' => true,
                'required' => true,
                'choices' => $this->choices,
            )
        );

        foreach ($form as $child) {
            $this->assertFalse($child->isRequired());
        }
    }

    public function testExpandedRadiosAreRequiredIfChoiceChildIsRequired()
    {
        $form = $this->factory->create(
            static::TESTED_TYPE,
            null,
            array(
                'class' => Book::class,
                'multiple' => false,
                'expanded' => true,
                'required' => true,
                'choices' => $this->choices,
            )
        );

        foreach ($form as $child) {
            $this->assertTrue($child->isRequired());
        }
    }

    public function testExpandedRadiosAreNotRequiredIfChoiceChildIsNotRequired()
    {
        $form = $this->factory->create(
            static::TESTED_TYPE,
            null,
            array(
                'class' => Book::class,
                'multiple' => false,
                'expanded' => true,
                'required' => false,
                'choices' => $this->choices,
            )
        );

        foreach ($form as $child) {
            $this->assertFalse($child->isRequired());
        }
    }
}
