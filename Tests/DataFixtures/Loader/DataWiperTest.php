<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Tests\DataFixtures\Loader;

use Propel\PropelBundle\Tests\TestCase;

/**
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class DataWiperTest extends TestCase
{
    protected $con = null;

    public function setUp()
    {
        $this->loadPropelQuickBuilder();

        $schema = <<<SCHEMA
<database name="book" defaultIdMethod="native">
    <table name="book" phpName="WipeTestBook">
        <column name="id" type="integer" required="true" primaryKey="true" autoIncrement="true" />
        <column name="name" type="varchar" size="255" primaryString="true" />
        <column name="slug" type="varchar" size="255" />
    </table>
</database>
SCHEMA;

        $builder = new \PropelQuickBuilder();
        $builder->setSchema($schema);
        $this->con = $builder->build();
    }

    public function testWipesExistingData()
    {
        $book = new \WipeTestBook();
        $book
            ->setName('Armageddon is near')
            ->setSlug('armageddon-is-near')
            ->save($this->con)
        ;

        $savedBook = \WipeTestBookPeer::doSelectOne(new \Criteria(), $this->con);
        $this->assertInstanceOf('WipeTestBook', $savedBook, 'The fixture has been saved correctly.');

        $builder = $this->getMockBuilder('Propel\PropelBundle\DataFixtures\Loader\DataWiper');
        $wipeout = $builder
            ->setMethods(array('loadMapBuilders'))
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $dbMap = new \DatabaseMap('book');
        $dbMap->addTableFromMapClass('WipeTestBookTableMap');
        $reflection = new \ReflectionObject($wipeout);
        $property = $reflection->getProperty('dbMap');
        $property->setAccessible(true);
        $property->setValue($wipeout, $dbMap);

        $wipeout
            ->expects($this->once())
            ->method('loadMapBuilders')
        ;

        $wipeout->load(array(), 'book');

        $this->assertCount(0, \WipeTestBookPeer::doSelect(new \Criteria(), $this->con));
    }
}