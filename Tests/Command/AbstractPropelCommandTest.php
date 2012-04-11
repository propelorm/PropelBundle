<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Tests\Command;

use Propel\PropelBundle\Tests\TestCase;
use Propel\PropelBundle\Command\AbstractPropelCommand;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class AbstractPropelCommandTest extends TestCase
{
    protected $command;

    public function setUp()
    {
        $this->command = new TestableAbstractPropelCommand('testable-command');
    }

    public function testParseDbName()
    {
        $dsn = 'mydsn#dbname=foo';
        $this->assertEquals('foo', $this->command->parseDbName($dsn));
    }

    public function testParseDbNameWithoutDbName()
    {
        $this->assertNull($this->command->parseDbName('foo'));
    }
}

class TestableAbstractPropelCommand extends AbstractPropelCommand
{
    public function parseDbName($dsn)
    {
        return parent::parseDbName($dsn);
    }
}
