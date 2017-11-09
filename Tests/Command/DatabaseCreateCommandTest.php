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
use Propel\PropelBundle\Command\DatabaseCreateCommand;

/**
 * @author Warnar Boekkooi <warnar@boekkooi.net>
 */
class DatabaseCreateCommandTest extends TestCase
{
    /** @var TestableDatabaseCreateCommand */
    protected $command;

    public function setUp()
    {
        $this->command = new TestableDatabaseCreateCommand();
    }

    public function tearDown()
    {
        $this->command = null;
    }

    /**
     * @dataProvider dataTemporaryConfiguration
     */
    public function testTemporaryConfiguration($name, $config, $expectedDsn)
    {
        $datasource = $this->command->getTemporaryConfiguration($name, $config);

        $this->assertArrayHasKey('datasources', $datasource);
        $this->assertArrayHasKey($name, $datasource['datasources']);
        $this->assertArrayHasKey('connection', $datasource['datasources'][$name]);
        $this->assertArrayHasKey('dsn', $datasource['datasources'][$name]['connection']);
        $this->assertEquals($expectedDsn, $datasource['datasources'][$name]['connection']['dsn']);
    }

    public function dataTemporaryConfiguration()
    {
        return array(
            array(
                'dbname',
                array('connection' => array('dsn' => 'mydsn:host=localhost;dbname=test_db;')),
                'mydsn:host=localhost;'
            ),
            array(
                'dbname_first',
                array('connection' => array('dsn' => 'mydsn:dbname=test_db;host=localhost')),
                'mydsn:host=localhost'
            ),
            array(
                'dbname_no_semicolon',
                array('connection' => array('dsn' => 'mydsn:host=localhost;dbname=test_db')),
                'mydsn:host=localhost;'
            ),
            array(
                'no_dbname',
                array('connection' => array('dsn' => 'mydsn:host=localhost;')),
                'mydsn:host=localhost;'
            ),
        );
    }
}

class TestableDatabaseCreateCommand extends DatabaseCreateCommand
{
    public function getTemporaryConfiguration($name, $config)
    {
        return parent::getTemporaryConfiguration($name, $config);
    }
}
