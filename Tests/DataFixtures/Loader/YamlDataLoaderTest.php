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
use Propel\PropelBundle\DataFixtures\Loader\YamlDataLoader;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class YamlDataLoaderTest extends TestCase
{
    protected $tmpfile;

    public function setUp()
    {
        $fixtures = <<<YML
\Foo\Bar:
    fb1:
        Id: 10
        Title: Hello
        Tags: null
    fb2:
        Id: 20
        Title: World
        Tags: [foo, bar, baz]
YML;
        $this->tmpfile = (string) tmpfile();
        file_put_contents($this->tmpfile, $fixtures);
    }

    public function tearDown()
    {
        unlink($this->tmpfile);
    }

    public function testTransformDataToArray()
    {
        $loader = new TestableYamlDataLoader();
        $array  = $loader->transformDataToArray($this->tmpfile);

        $this->assertTrue(is_array($array), 'Result is an array');
        $this->assertEquals(1, count($array), 'There is one class');
        $this->assertArrayHasKey('\Foo\Bar', $array);

        $subarray = $array['\Foo\Bar'];
        $this->assertTrue(is_array($subarray), 'Result contains a sub-array');
        $this->assertEquals(2, count($subarray), 'There is two fixtures objects');
        $this->assertArrayHasKey('fb1', $subarray);
        $this->assertArrayHasKey('fb2', $subarray);
        $this->assertTrue(is_array($subarray['fb2']['Tags']));
        $this->assertEquals(array('foo', 'bar', 'baz'), $subarray['fb2']['Tags']);
    }
}

class TestableYamlDataLoader extends YamlDataLoader
{
    public function __construct()
    {
    }

    public function transformDataToArray($data)
    {
        return parent::transformDataToArray($data);
    }
}
