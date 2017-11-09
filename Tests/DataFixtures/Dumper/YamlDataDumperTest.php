<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Tests\DataFixtures\Dumper;

use Propel\PropelBundle\Tests\DataFixtures\TestCase;
use Propel\PropelBundle\DataFixtures\Dumper\YamlDataDumper;
use Symfony\Component\Config\Loader\LoaderInterface;

/**
 * @author William Durand <william.durand1@gmail.com>
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class YamlDataDumperTest extends TestCase
{
    public function testYamlDump()
    {
        $author = new \Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\BookAuthor();
        $author->setName('A famous one')->save($this->con);

        $complementary = new \stdClass();
        $complementary->first_word_date = '2012-01-01';

        $book = new \Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\Book();
        $book
            ->setName('An important one')
            ->setAuthorId(1)
            ->setComplementaryInfos($complementary)
            ->save($this->con)
        ;

        $filename = $this->getTempFile();

        $loader = new YamlDataDumper(__DIR__.'/../../Fixtures/DataFixtures/Loader');
        $loader->dump($filename);

        $expected = $this->getYamlForSymfonyVersion();

        $result = file_get_contents($filename);
        $this->assertEquals($expected, $result);
    }

    protected function getYamlForSymfonyVersion()
    {
        if (version_compare(AppKernel::VERSION, '2.7.0', '<')) {
            return <<<YAML
Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\BookAuthor:
    BookAuthor_1:
        id: '1'
        name: 'A famous one'
Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\Book:
    Book_1:
        id: '1'
        name: 'An important one'
        author_id: BookAuthor_1
        complementary_infos: !!php/object:O:8:"stdClass":1:{s:15:"first_word_date";s:10:"2012-01-01";}

YAML;
        }

        return <<<YAML
Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\BookAuthor:
    BookAuthor_1:
        id: '1'
        name: 'A famous one'
Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\Book:
    Book_1:
        id: '1'
        name: 'An important one'
        author_id: BookAuthor_1
        complementary_infos: !php/object:O:8:"stdClass":1:{s:15:"first_word_date";s:10:"2012-01-01";}

YAML;
    }
}

class AppKernel extends \Symfony\Component\HttpKernel\Kernel
{
    public function registerBundles()
    {
        // TODO: Implement registerBundles() method.
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        // TODO: Implement registerContainerConfiguration() method.
    }

}
