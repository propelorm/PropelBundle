<?php

namespace Propel\Bundle\PropelBundle\Tests;

class AutoloadAliasTest extends \PHPUnit_Framework_TestCase
{
    public function testOldNamespaceWorks()
    {
        $inflector = new \Propel\PropelBundle\Util\PropelInflector();

        static::assertInstanceOf('Propel\PropelBundle\Util\PropelInflector', $inflector);
        static::assertInstanceOf('Propel\Bundle\PropelBundle\Util\PropelInflector', $inflector);
    }
}
