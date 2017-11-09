<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Tests\Util;

use Propel\PropelBundle\Tests\TestCase;
use Propel\PropelBundle\Util\PropelInflector;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class PropelInflectorTest extends TestCase
{
    /**
     * @dataProvider dataProviderForTestCamelize
     */
    public function testCamelize($word, $expected)
    {
        $this->assertEquals($expected, PropelInflector::camelize($word));
    }

    public static function dataProviderForTestCamelize()
    {
        return array(
            array('', ''),
            array(null, null),
            array('foo', 'foo'),
            array('Foo', 'foo'),
            array('fooBar', 'fooBar'),
            array('FooBar', 'fooBar'),
            array('Foo_bar', 'fooBar'),
            array('Foo_Bar', 'fooBar'),
            array('Foo Bar', 'fooBar'),
            array('Foo bar Baz', 'fooBarBaz'),
            array('Foo_Bar_Baz', 'fooBarBaz'),
            array('foo_bar_baz', 'fooBarBaz'),
        );
    }
}
