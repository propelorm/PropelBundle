<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Tests\DataFixtures\Loader;

use Propel\PropelBundle\Tests\DataFixtures\TestCase;

/**
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class DataWiperTest extends TestCase
{
    public function testWipesExistingData()
    {
        $author = new \Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\BookAuthor();
        $author->setName('Some famous author');

        $book = new \Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\Book();
        $book
            ->setName('Armageddon is near')
            ->setBookAuthor($author)
            ->save($this->con)
        ;

        $savedBook = \Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\BookPeer::doSelectOne(new \Criteria(), $this->con);
        $this->assertInstanceOf('Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\Book', $savedBook, 'The fixture has been saved correctly.');

        $builder = $this->getMockBuilder('Propel\PropelBundle\DataFixtures\Loader\DataWiper');
        $wipeout = $builder
            ->setMethods(array('loadMapBuilders'))
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $dbMap = new \DatabaseMap('default');
        $dbMap->addTableFromMapClass('Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\map\BookTableMap');
        $reflection = new \ReflectionObject($wipeout);
        $property = $reflection->getProperty('dbMap');
        $property->setAccessible(true);
        $property->setValue($wipeout, $dbMap);

        $wipeout
            ->expects($this->once())
            ->method('loadMapBuilders')
        ;

        $wipeout->load(array(), 'default');

        $this->assertCount(0, \Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\BookPeer::doSelect(new \Criteria(), $this->con));
    }
}
