<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */
namespace Propel\Bundle\PropelBundle\Tests\DataFixtures\Loader;

use Propel\Bundle\PropelBundle\DataFixtures\Loader\XmlDataLoader;
use Propel\Bundle\PropelBundle\Tests\DataFixtures\TestCase;

/**
 * @author William Durand <william.durand1@gmail.com>
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class XmlDataLoaderTest extends TestCase
{
    public function testXmlLoad()
    {
        $fixtures = <<<XML
<Fixtures>
    <BookAuthor Namespace="Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader">
        <BookAuthor_1 id="1" name="A famous one" />
    </BookAuthor>
    <Book Namespace="Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader">
        <Book_1 id="1" name="An important one" author_id="BookAuthor_1" />
    </Book>
</Fixtures>
XML;

        $filename = $this->getTempFile($fixtures);

        $loader = new XmlDataLoader(__DIR__.'/../../Fixtures/DataFixtures/Loader');
        $loader->load(array($filename), 'default');

        $books = \Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\BookPeer::doSelect(new \Criteria(), $this->con);
        $this->assertCount(1, $books);

        $book = $books[0];
        $this->assertInstanceOf('Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\BookAuthor', $book->getBookAuthor());
    }
}
