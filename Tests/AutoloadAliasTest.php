<?php

namespace Propel\Bundle\PropelBundle\Tests;

use Propel\Bundle\PropelBundle\Util\PropelInflector;

class AutoloadAliasTest extends \PHPUnit_Framework_TestCase
{
    public function testOldNamespaceWorks()
    {
        $inflector = new PropelInflector();

        static::assertInstanceOf('Propel\PropelBundle\Util\PropelInflector', $inflector);
        static::assertInstanceOf('Propel\Bundle\PropelBundle\Util\PropelInflector', $inflector);
    }
}
