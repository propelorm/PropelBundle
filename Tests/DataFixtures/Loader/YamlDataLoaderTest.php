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
use Propel\PropelBundle\DataFixtures\Loader\YamlDataLoader;

/**
 * @author William Durand <william.durand1@gmail.com>
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class YamlDataLoaderTest extends TestCase
{
    public function testYamlLoadOneToMany()
    {
        $fixtures = <<<YAML
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
        $filename = $this->getTempFile($fixtures);

        $loader = new YamlDataLoader(__DIR__.'/../../Fixtures/DataFixtures/Loader');
        $loader->load(array($filename), 'default');

        $books = \Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\BookPeer::doSelect(new \Criteria(), $this->con);
        $this->assertCount(1, $books);

        $book = $books[0];
        $this->assertInstanceOf('Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\BookAuthor', $book->getBookAuthor());
    }

    public function testYamlLoadManyToMany()
    {
        $schema = <<<XML
<database name="default" package="vendor.bundles.Propel.PropelBundle.Tests.Fixtures.DataFixtures.Loader" namespace="Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader" defaultIdMethod="native">
    <table name="table_book" phpName="YamlManyToManyBook">
        <column name="id" type="integer" primaryKey="true" />
        <column name="name" type="varchar" size="255" />
    </table>

    <table name="table_author" phpName="YamlManyToManyAuthor">
        <column name="id" type="integer" primaryKey="true" />
        <column name="name" type="varchar" size="255" />
    </table>

    <table name="table_book_author" phpName="YamlManyToManyBookAuthor" isCrossRef="true">
        <column name="book_id" type="integer" required="true" primaryKey="true" />
        <column name="author_id" type="integer" required="true" primaryKey="true" />

        <foreign-key foreignTable="table_book" phpName="Book" onDelete="CASCADE" onUpdate="CASCADE">
            <reference local="book_id" foreign="id" />
        </foreign-key>
        <foreign-key foreignTable="table_author" phpName="Author" onDelete="CASCADE" onUpdate="CASCADE">
            <reference local="author_id" foreign="id" />
        </foreign-key>
    </table>
</database>
XML;

        $fixtures = <<<YAML
Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyBook:
    Book_1:
        id: 1
        name: 'An important one'
    Book_2:
        id: 2
        name: 'Les misérables'

Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyAuthor:
    Author_1:
        id: 1
        name: 'A famous one'
    Author_2:
        id: 2
        name: 'Victor Hugo'
        table_book_authors: [ Book_2 ]

Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyBookAuthor:
    BookAuthor_1:
        book_id: Book_1
        author_id: Author_1
YAML;

        $filename = $this->getTempFile($fixtures);

        $builder = new \PropelQuickBuilder();
        $builder->setSchema($schema);
        $con = $builder->build();

        $loader = new YamlDataLoader(__DIR__.'/../../Fixtures/DataFixtures/Loader');
        $loader->load(array($filename), 'default');

        $books = \Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyBookPeer::doSelect(new \Criteria(), $con);
        $this->assertCount(2, $books);
        $this->assertInstanceOf('Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyBook', $books[0]);
        $this->assertInstanceOf('Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyBook', $books[1]);

        $authors = \Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyAuthorPeer::doSelect(new \Criteria(), $con);
        $this->assertCount(2, $authors);
        $this->assertInstanceOf('Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyAuthor', $authors[0]);
        $this->assertInstanceOf('Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyAuthor', $authors[1]);

        $bookAuthors = \Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyBookAuthorPeer::doSelect(new \Criteria(), $con);
        $this->assertCount(2, $bookAuthors);
        $this->assertInstanceOf('Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyBookAuthor', $bookAuthors[0]);
        $this->assertInstanceOf('Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyBookAuthor', $bookAuthors[1]);

        $this->assertEquals('Victor Hugo', $authors[1]->getName());
        $this->assertTrue($authors[1]->getBooks()->contains($books[1]));
        $this->assertEquals('Les misérables', $authors[1]->getBooks()->get(0)->getName());
    }

    public function testYamlLoadManyToManyMultipleFiles()
    {
        $schema = <<<XML
<database name="default" package="vendor.bundles.Propel.PropelBundle.Tests.Fixtures.DataFixtures.Loader" namespace="Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader" defaultIdMethod="native">
    <table name="table_book_multiple" phpName="YamlManyToManyMultipleFilesBook">
        <column name="id" type="integer" primaryKey="true" />
        <column name="name" type="varchar" size="255" />
    </table>

    <table name="table_author_multiple" phpName="YamlManyToManyMultipleFilesAuthor">
        <column name="id" type="integer" primaryKey="true" />
        <column name="name" type="varchar" size="255" />
    </table>

    <table name="table_book_author_multiple" phpName="YamlManyToManyMultipleFilesBookAuthor" isCrossRef="true">
        <column name="book_id" type="integer" required="true" primaryKey="true" />
        <column name="author_id" type="integer" required="true" primaryKey="true" />

        <foreign-key foreignTable="table_book_multiple" phpName="Book" onDelete="CASCADE" onUpdate="CASCADE">
            <reference local="book_id" foreign="id" />
        </foreign-key>
        <foreign-key foreignTable="table_author_multiple" phpName="Author" onDelete="CASCADE" onUpdate="CASCADE">
            <reference local="author_id" foreign="id" />
        </foreign-key>
    </table>
</database>
XML;

        $fixtures1 = <<<YAML
Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyMultipleFilesBook:
    Book_2:
        id: 2
        name: 'Les misérables'

Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyMultipleFilesAuthor:
    Author_1:
        id: 1
        name: 'A famous one'

Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyMultipleFilesBookAuthor:
    BookAuthor_1:
        book_id: Book_1
        author_id: Author_1
YAML;

        $fixtures2 = <<<YAML
Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyMultipleFilesBook:
    Book_1:
        id: 1
        name: 'An important one'

Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyMultipleFilesAuthor:
    Author_2:
        id: 2
        name: 'Victor Hugo'
        table_book_author_multiples: [ Book_2 ]
YAML;

        $filename1 = $this->getTempFile($fixtures1);
        $filename2 = $this->getTempFile($fixtures2);

        $builder = new \PropelQuickBuilder();
        $builder->setSchema($schema);
        $con = $builder->build();

        $loader = new YamlDataLoader(__DIR__.'/../../Fixtures/DataFixtures/Loader');
        $loader->load(array($filename1, $filename2), 'default');

        $books = \Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyMultipleFilesBookPeer::doSelect(new \Criteria(), $con);
        $this->assertCount(2, $books);
        $this->assertInstanceOf('Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyMultipleFilesBook', $books[0]);
        $this->assertInstanceOf('Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyMultipleFilesBook', $books[1]);

        $authors = \Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyMultipleFilesAuthorPeer::doSelect(new \Criteria(), $con);
        $this->assertCount(2, $authors);
        $this->assertInstanceOf('Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyMultipleFilesAuthor', $authors[0]);
        $this->assertInstanceOf('Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyMultipleFilesAuthor', $authors[1]);

        $bookAuthors = \Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyMultipleFilesBookAuthorPeer::doSelect(new \Criteria(), $con);
        $this->assertCount(2, $bookAuthors);
        $this->assertInstanceOf('Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyMultipleFilesBookAuthor', $bookAuthors[0]);
        $this->assertInstanceOf('Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyMultipleFilesBookAuthor', $bookAuthors[1]);

        $this->assertEquals('Victor Hugo', $authors[1]->getName());
        $this->assertTrue($authors[1]->getBooks()->contains($books[1]));
        $this->assertEquals('Les misérables', $authors[1]->getBooks()->get(0)->getName());
    }

    public function testLoadSelfReferencing()
    {
        $fixtures = <<<YAML
Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\BookAuthor:
    BookAuthor_1:
        id: '1'
        name: 'to be announced'
    BookAuthor_2:
        id: BookAuthor_1
        name: 'A famous one'

YAML;
        $filename = $this->getTempFile($fixtures);

        $loader = new YamlDataLoader(__DIR__.'/../../Fixtures/DataFixtures/Loader');
        $loader->load(array($filename), 'default');

        $books = \Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\BookPeer::doSelect(new \Criteria(), $this->con);
        $this->assertCount(0, $books);

        $authors = \Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\BookAuthorPeer::doSelect(new \Criteria(), $this->con);
        $this->assertCount(1, $authors);

        $author = $authors[0];
        $this->assertEquals('A famous one', $author->getName());
    }

    public function testLoaderWithPhp()
    {
        $fixtures = <<<YAML
Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\BookAuthor:
    BookAuthor_1:
        id: '1'
        name: <?php echo "to be announced"; ?>

YAML;
        $filename = $this->getTempFile($fixtures);

        $loader = new YamlDataLoader(__DIR__.'/../../Fixtures/DataFixtures/Loader');
        $loader->load(array($filename), 'default');

        $books = \Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\BookPeer::doSelect(new \Criteria(), $this->con);
        $this->assertCount(0, $books);

        $authors = \Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\BookAuthorPeer::doSelect(new \Criteria(), $this->con);
        $this->assertCount(1, $authors);

        $author = $authors[0];
        $this->assertEquals('to be announced', $author->getName());
    }

    public function testLoadWithoutFaker()
    {
        $fixtures = <<<YAML
Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\BookAuthor:
    BookAuthor_1:
        id: '1'
        name: <?php echo \$faker('word'); ?>

YAML;
        $filename = $this->getTempFile($fixtures);

        $loader = new YamlDataLoader(__DIR__.'/../../Fixtures/DataFixtures/Loader');
        $loader->load(array($filename), 'default');

        $books = \Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\BookPeer::doSelect(new \Criteria(), $this->con);
        $this->assertCount(0, $books);

        $authors = \Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\BookAuthorPeer::doSelect(new \Criteria(), $this->con);
        $this->assertCount(1, $authors);

        $author = $authors[0];
        $this->assertEquals('word', $author->getName());
    }

    public function testLoadWithFaker()
    {
        if (!class_exists('Faker\Factory')) {
            $this->markTestSkipped('Faker is mandatory');
        }

        $fixtures = <<<YAML
Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\Book:
    Book_1:
        id: '1'
        name: <?php \$faker('word'); ?>
        description: <?php \$faker('sentence'); ?>

YAML;
        $filename  = $this->getTempFile($fixtures);
        $container = $this->getContainer();
        $container->set('faker.generator', \Faker\Factory::create());

        $loader = new YamlDataLoader(__DIR__.'/../../Fixtures/DataFixtures/Loader', $container);
        $loader->load(array($filename), 'default');

        $books = \Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\BookPeer::doSelect(new \Criteria(), $this->con);
        $this->assertCount(1, $books);

        $book = $books[0];
        $this->assertNotNull($book->getName());
        $this->assertNotEquals('null', strtolower($book->getName()));
        $this->assertRegexp('#[a-z]+#', $book->getName());
        $this->assertNotNull($book->getDescription());
        $this->assertNotEquals('null', strtolower($book->getDescription()));
        $this->assertRegexp('#[\w ]+#', $book->getDescription());
    }
}
