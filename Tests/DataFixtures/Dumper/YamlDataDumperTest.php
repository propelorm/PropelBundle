<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Tests\DataFixtures\Dumper;

use Propel\Runtime\Propel;
use Propel\Bundle\PropelBundle\Tests\DataFixtures\TestCase;
use Propel\Bundle\PropelBundle\DataFixtures\Dumper\YamlDataDumper;

/**
 * @author William Durand <william.durand1@gmail.com>
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class YamlDataDumperTest extends TestCase
{
    public function testYamlDump()
    {
        $author = new \Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\CoolBookAuthor();
        $author->setName('A famous one')->save($this->con);

        $complementary = new \stdClass();
        $complementary->first_word_date = '2012-01-01';

        $book = new \Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\CoolBook();
        $book
            ->setName('An important one')
            ->setAuthorId(1)
            ->setComplementaryInfos($complementary)
            ->save($this->con)
        ;

        $filename = $this->getTempFile();

        $loader = new YamlDataDumper(__DIR__.'/../../Fixtures/DataFixtures/Loader', array());
        $loader->dump($filename);

        $expected = <<<YAML
\Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\CoolBookAuthor:
    CoolBookAuthor_1:
        id: '1'
        name: 'A famous one'
\Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\CoolBook:
    CoolBook_1:
        id: '1'
        name: 'An important one'
        author_id: CoolBookAuthor_1
        complementary_infos: !php/object:O:8:"stdClass":1:{s:15:"first_word_date";s:10:"2012-01-01";}

YAML;

        $result = file_get_contents($filename);

        //yaml changed the way objects are serialized in
        // -> https://github.com/symfony/yaml/commit/d5a7902da7e5af069bb8fdcfcf029a229deb1111
        //so we need to replace old behavior with new, to get this test working in all versions
        $result = str_replace(' !!php/object', ' !php/object', $result);

        $this->assertEquals($expected, $result);
    }
}
