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
    public function testTransformArrayToData()
    {
        $expected = <<<YML
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

        $array = array(
            '\Foo\Bar' => array(
                'fb1' => array('Id' => 10, 'Title' => 'Hello', 'Tags' => null),
                'fb2' => array('Id' => 20, 'Title' => 'World', 'Tags' => array('foo', 'bar', 'baz'))
            )
        );

        $loader = new TestableYamlDataDumper();
        $result = $loader->transformArrayToData($array);
        $this->assertSame($expected, $result);
    }
}

class TestableYamlDataDumper extends YamlDataDumper
{
    public function __construct()
    {
    }

    public function transformArrayToData($array)
    {
        return parent::transformArrayToData($array);
    }
}
