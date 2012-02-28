<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Tests\DataFixtures\Dumper;

use Propel\PropelBundle\Tests\TestCase;
use Propel\PropelBundle\DataFixtures\Dumper\YamlDataDumper;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class YamlDataDumperTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->loadPropelQuickBuilder();

        $schema = <<<XML
<database name="default" package="vendor.bundles.Propel.PropelBundle.Tests.Fixtures.DataFixtures.Loader" namespace="Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader" defaultIdMethod="native">
    <table name="book">
        <column name="id" type="integer" primaryKey="true" />
        <column name="name" type="varchar" size="255" />
        <column name="author_id" type="integer" required="false" defaultValue="null" />

        <foreign-key foreignTable="book_author" onDelete="RESTRICT" onUpdate="CASCADE">
            <reference local="author_id" foreign="id" />
        </foreign-key>
    </table>

    <table name="book_author">
        <column name="id" type="integer" primaryKey="true" />
        <column name="name" type="varchar" size="255" />
    </table>
</database>
XML;

        $builder = new \PropelQuickBuilder();
        $builder->setSchema($schema);
        if (!class_exists('Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\Book')) {
            $builder->setClassTargets(array('peer', 'object', 'query', 'peerstub', 'objectstub', 'querystub'));
        } else {
            $builder->setClassTargets(array());
        }

        $this->con = $builder->build();
    }

    public function testYamlDump()
    {
        $author = new \Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\BookAuthor();
        $author->setName('A famous one')->save($this->con);

        $book = new \Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\Book;
        $book
            ->setName('An important one')
            ->setAuthorId(1)
            ->save($this->con)
        ;

        $filename = tempnam(sys_get_temp_dir(), 'yaml_datadumper_test');
        @unlink($filename);

        $loader = new YamlDataDumper(__DIR__.'/../../Fixtures/DataFixtures/Loader');
        $loader->dump($filename);

        $expected = <<<YAML
Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\BookAuthor:
    BookAuthor_1:
        id: '1'
        name: 'A famous one'
Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\Book:
    Book_1:
        id: '1'
        name: 'An important one'
        author_id: BookAuthor_1

YAML;

        $result = file_get_contents($filename);
        $this->assertEquals($expected, $result);

        @unlink($filename);
    }
}
