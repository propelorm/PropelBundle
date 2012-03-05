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
use Propel\PropelBundle\DataFixtures\Loader\XmlDataLoader;

/**
 * @author William Durand <william.durand1@gmail.com>
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class XmlDataLoaderTest extends TestCase
{
    protected $tmpfile;

    protected function setUp()
    {
        parent::setUp();

        $fixtures = <<<XML
<Fixtures>
    <BookAuthor Namespace="Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader">
        <BookAuthor_1 id="1" name="A famous one" />
    </BookAuthor>
    <Book Namespace="Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader">
        <Book_1 id="1" name="An important one" author_id="BookAuthor_1" />
    </Book>
</Fixtures>
XML;
        $this->tmpfile = (string) tmpfile();
        file_put_contents($this->tmpfile, $fixtures);
    }

    protected function tearDown()
    {
        unlink($this->tmpfile);
    }

    public function testXmlLoad()
    {
        $loader = new XmlDataLoader(__DIR__.'/../../Fixtures/DataFixtures/Loader');
        $loader->load(array($this->tmpfile), 'default');

        $books = \Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\BookPeer::doSelect(new \Criteria(), $this->con);
        $this->assertCount(1, $books);

        $book = $books[0];
        $this->assertInstanceOf('Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\BookAuthor', $book->getBookAuthor());
    }
}
