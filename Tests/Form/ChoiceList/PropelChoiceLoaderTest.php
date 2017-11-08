<?php

namespace Propel\Bundle\PropelBundle\Tests\Form\ChoiceList;

use Propel\Bundle\PropelBundle\Form\ChoiceList\PropelChoiceLoader;
use Propel\Bundle\PropelBundle\Tests\Fixtures\Model\Book;
use Propel\Bundle\PropelBundle\Tests\Fixtures\Model\map\BookTableMap;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\Factory\ChoiceListFactoryInterface;

/**
 * @author Jérémie Augustin <jeremie.augustin@pixel-cookers.com>
 */
class PropelChoiceLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ChoiceListFactoryInterface
     */
    private $factory;

    private $class;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\ModelCriteria
     */
    private $query;

    private $obj1;

    private $obj2;

    private $obj3;

    protected function setUp()
    {
        $this->factory = $this->getMockBuilder('Symfony\Component\Form\ChoiceList\Factory\ChoiceListFactoryInterface')->getMock();
        $this->class = Book::class;

        $this->obj1 = new Book();
        $this->obj1->setId(1);
        $this->obj1->setName('book 1');

        $this->obj2 = new Book();
        $this->obj2->setId(2);
        $this->obj2->setName('book 2');

        $this->obj3 = new Book();
        $this->obj3->setId(3);
        $this->obj3->setName('book 3');

        $this->query = $this->getMockBuilder('\ModelCriteria')
            ->disableOriginalConstructor()
            ->getMock();

        $this->query->expects($this->any())
            ->method('getTableMap')
            ->willReturn(new BookTableMap());
    }

    public function testLoadChoiceList()
    {
        $loader = new PropelChoiceLoader(
            $this->class,
            $this->query
        );

        $choices = new \PropelObjectCollection();
        $choices->setData(array($this->obj1, $this->obj2, $this->obj3));
        $value = function () {};
        $choiceList = new ArrayChoiceList($choices, $value);

        $this->query->expects($this->once())
            ->method('find')
            ->willReturn($choices);

        $this->assertEquals($choiceList, $loader->loadChoiceList($value));
        // no further loads on subsequent calls
        $this->assertEquals($choiceList, $loader->loadChoiceList($value));

    }

    public function testLoadChoiceListWithNameIdentifier()
    {
        $loader = new PropelChoiceLoader(
            $this->class,
            $this->query,
            'SLUG'
        );

        $choices = new \PropelObjectCollection();
        $choices->setData(array($this->obj1, $this->obj2, $this->obj3));
        $value = function () {};
        $choiceList = new ArrayChoiceList($choices, $value);

        $this->query->expects($this->once())
            ->method('find')
            ->willReturn($choices);

        $this->assertEquals($choiceList, $loader->loadChoiceList($value));
        // no further loads on subsequent calls
        $this->assertEquals($choiceList, $loader->loadChoiceList($value));

    }

    public function testLoadValuesForChoices()
    {
        $loader = new PropelChoiceLoader(
            $this->class,
            $this->query
        );
        $this->query->expects($this->never())
            ->method('find');

        $this->assertSame(array('2', '3'), $loader->loadValuesForChoices(array($this->obj2, $this->obj3)));
        // no further loads on subsequent calls
        $this->assertSame(array('2', '3'), $loader->loadValuesForChoices(array($this->obj2, $this->obj3)));
    }

    public function testLoadValuesForChoicesDoesNotLoadIfEmptyChoices()
    {
        $loader = new PropelChoiceLoader(
            $this->class,
            $this->query
        );
        $this->query->expects($this->never())
            ->method('find');
        $this->assertSame(array(), $loader->loadValuesForChoices(array()));
    }

    public function testLoadValuesForChoicesDoesNotLoadIfSingleIntId()
    {
        $loader = new PropelChoiceLoader(
            $this->class,
            $this->query
        );
        $this->query->expects($this->never())
            ->method('find');
        $this->assertSame(array('2'), $loader->loadValuesForChoices(array($this->obj2)));
    }

    public function testLoadValuesForChoicesLoadsIfSingleIntIdAndValueGiven()
    {
        $loader = new PropelChoiceLoader(
            $this->class,
            $this->query
        );
        $choices = new \PropelObjectCollection();
        $choices->setData(array($this->obj1, $this->obj2, $this->obj3));
        $value = function (Book $object) { return $object->getName(); };
        $this->query->expects($this->once())
            ->method('find')
            ->willReturn($choices);
        $this->assertSame(array('book 2'), $loader->loadValuesForChoices(
            array($this->obj2),
            $value
        ));
    }

    public function testLoadChoicesForValues()
    {
        $loader = new PropelChoiceLoader(
            $this->class,
            $this->query
        );
        $choices = new \PropelObjectCollection();
        $choices->setData(array($this->obj2, $this->obj3));
        $this->query->expects($this->once())
            ->method('filterBy')
            ->willReturnSelf();
        $this->query->expects($this->once())
            ->method('find')
            ->willReturn($choices);
        $this->assertSame(array($this->obj2, $this->obj3), $loader->loadChoicesForValues(array('2', '3')));
    }

    public function testLoadChoicesForValuesDoesNotLoadIfEmptyValues()
    {
        $loader = new PropelChoiceLoader(
            $this->class,
            $this->query
        );
        $this->query->expects($this->never())
            ->method('find');
        $this->assertSame(array(), $loader->loadChoicesForValues(array()));
    }

    public function testLoadChoicesForValuesLoadsOnlyChoicesIfSingleIntId()
    {
        $loader = new PropelChoiceLoader(
            $this->class,
            $this->query
        );

        $choices = new \PropelObjectCollection();
        $choices->setData(array($this->obj2, $this->obj3));

        $this->query->expects($this->once())
            ->method('filterBy')
            ->willReturnSelf();
        $this->query->expects($this->once())
            ->method('find')
            ->willReturn($choices);


        $this->assertSame(
            array(4 => $this->obj3, 7 => $this->obj2),
            $loader->loadChoicesForValues(array(4 => '3', 7 => '2'))
        );
    }

    public function testLoadChoicesForValuesLoadsAllIfSingleIntIdAndValueGiven()
    {
        $loader = new PropelChoiceLoader(
            $this->class,
            $this->query
        );
        $choices = new \PropelObjectCollection();
        $choices->setData(array($this->obj1, $this->obj2, $this->obj3));
        $value = function (Book $object) { return $object->getName(); };
        $this->query->expects($this->once())
            ->method('find')
            ->willReturn($choices);
        $this->assertSame(array($this->obj2), $loader->loadChoicesForValues(
            array('book 2'),
            $value
        ));
    }
}
