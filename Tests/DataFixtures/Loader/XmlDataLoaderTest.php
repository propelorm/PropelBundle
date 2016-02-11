<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Tests\DataFixtures\Loader;

use Propel\Runtime\Propel;
use Propel\Bundle\PropelBundle\Tests\DataFixtures\TestCase;
use Propel\Bundle\PropelBundle\DataFixtures\Loader\XmlDataLoader;

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
    <CoolBookAuthor Namespace="Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader">
        <CoolBookAuthor_1 id="1" name="A famous one" />
    </CoolBookAuthor>
    <CoolBook Namespace="Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader">
        <CoolBook_1 id="1" name="An important one" author_id="CoolBookAuthor_1" />
    </CoolBook>
</Fixtures>
XML;

        $filename = $this->getTempFile($fixtures);

        $loader = new XmlDataLoader(__DIR__.'/../../Fixtures/DataFixtures/Loader', array());
        $loader->load(array($filename), 'default');

        $books = \Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\CoolBookQuery::create()->find($this->con);
        $this->assertCount(1, $books);

        $book = $books[0];
        $this->assertInstanceOf('Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\CoolBookAuthor', $book->getCoolBookAuthor());
    }
}
