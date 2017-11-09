<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Tests\DependencyInjection;

use Propel\PropelBundle\Tests\TestCase;
use Propel\PropelBundle\DependencyInjection\PropelExtension;
use Symfony\Component\DependencyInjection\Container;

class PropelExtensionTest extends TestCase
{
    public function testLoad()
    {
        $container = $this->getContainer();
        $loader = new PropelExtension();
        try {
            $loader->load(array(array()), $container);
            $this->fail();
        } catch (\Exception $e) {
            $this->assertInstanceOf('InvalidArgumentException', $e,
                '->load() throws an \InvalidArgumentException if the Propel path is not set');
        }

        $container = $this->getContainer();
        $loader = new PropelExtension();
        try {
            $loader->load(array(array(
                'path' => '/propel',
                'dbal' => array(),
            )), $container);
            $this->fail();
        } catch (\Exception $e) {
            $this->assertInstanceOf('InvalidArgumentException', $e,
                '->load() throws an \InvalidArgumentException if the Phing path is not set.');
        }

        $container = $this->getContainer();
        $loader = new PropelExtension();
        $loader->load(array(array(
            'path'       => '/propel',
            'phing_path' => '/phing',
            'dbal'       => array()
        )), $container);
        $this->assertEquals('/propel',  $container->getParameter('propel.path'), '->load() requires the Propel path');
        $this->assertEquals('/phing',   $container->getParameter('propel.phing_path'), '->load() requires the Phing path');
    }

    public function testDbalLoad()
    {
        $container = $this->getContainer();
        $loader = new PropelExtension();

        $loader->load(array(array(
            'path'       => '/propel',
            'phing_path' => '/phing',
            'dbal' => array(
                'default_connection' => 'foo',
            )
        )), $container);
        $this->assertEquals('foo', $container->getParameter('propel.dbal.default_connection'), '->dbalLoad() overrides existing configuration options');

        $container = $this->getContainer();
        $loader = new PropelExtension();

        $loader->load(array(array(
            'path'          => '/propel',
            'phing_path'    => '/phing',
            'dbal'          => array(
                'password' => 'foo',
            )
        )), $container);

        $arguments = $container->getDefinition('propel.configuration')->getArguments();
        $config = $arguments[0];

        $this->assertEquals('foo', $config['datasources']['default']['connection']['password']);
        $this->assertEquals('root', $config['datasources']['default']['connection']['user']);

        $loader->load(array(array(
            'path' => '/propel',
            'dbal' => array(
                'user' => 'foo',
            )
        )), $container);
        $this->assertEquals('foo', $config['datasources']['default']['connection']['password']);
        $this->assertEquals('root', $config['datasources']['default']['connection']['user']);

    }

    public function testDbalLoadCascade()
    {
        $container = $this->getContainer();
        $loader = new PropelExtension();

        $config_base = array(
            'path'       => '/propel',
            'phing_path' => '/propel',
        );

        $config_prod = array('dbal' => array(
            'user'      => 'toto',
            'password'  => 'titi',
            'dsn'       => 'foobar',
            'driver'    => 'my_driver',
            'options'   => array('o1', 'o2')
        ));

        $config_dev = array('dbal' => array(
            'user'      => 'toto_dev',
            'password'  => 'titi_dev',
            'dsn'       => 'foobar',
        ));

        $configs = array($config_base, $config_prod, $config_dev);

        $loader->load($configs, $container);

        $arguments = $container->getDefinition('propel.configuration')->getArguments();
        $config = $arguments[0];
        $this->assertEquals('toto_dev',  $config['datasources']['default']['connection']['user']);
        $this->assertEquals('titi_dev',  $config['datasources']['default']['connection']['password']);
        $this->assertEquals('foobar',    $config['datasources']['default']['connection']['dsn']);
        $this->assertEquals('my_driver', $config['datasources']['default']['adapter']);
        $this->assertEquals('o1',        $config['datasources']['default']['connection']['options'][0]);
        $this->assertEquals('o2',        $config['datasources']['default']['connection']['options'][1]);
    }

    public function testDbalLoadMultipleConnections()
    {
        $container = $this->getContainer();
        $loader = new PropelExtension();

        $config_base = array(
            'path'       => '/propel',
            'phing_path' => '/phing',
        );

        $config_mysql = array(
            'user'      => 'mysql_usr',
            'password'  => 'mysql_pwd',
            'dsn'       => 'mysql_dsn',
            'driver'    => 'mysql',
        );

        $config_sqlite = array(
            'user'      => 'sqlite_usr',
            'password'  => 'sqlite_pwd',
            'dsn'       => 'sqlite_dsn',
            'driver'    => 'sqlite',
        );

        $config_connections = array(
            'default_connection' => 'sqlite',
            'connections' => array('mysql' => $config_mysql, 'sqlite' => $config_sqlite,
        ));

        $configs = array($config_base, array('dbal' => $config_connections));

        $loader->load($configs, $container);

        $arguments = $container->getDefinition('propel.configuration')->getArguments();
        $config = $arguments[0];
        $this->assertEquals('sqlite', $container->getParameter('propel.dbal.default_connection'));
        $this->assertEquals('sqlite_usr',  $config['datasources']['sqlite']['connection']['user']);
        $this->assertEquals('sqlite_pwd',  $config['datasources']['sqlite']['connection']['password']);
        $this->assertEquals('sqlite_dsn',  $config['datasources']['sqlite']['connection']['dsn']);
        $this->assertEquals('sqlite',      $config['datasources']['sqlite']['adapter']);

        $config_connections = array(
            'default_connection' => 'mysql',
            'connections' => array('mysql' => $config_mysql, 'sqlite' => $config_sqlite,
        ));

        $configs = array($config_base, array('dbal' => $config_connections));

        $loader->load($configs, $container);

        $arguments = $container->getDefinition('propel.configuration')->getArguments();
        $config = $arguments[0];
        $this->assertEquals('mysql', $container->getParameter('propel.dbal.default_connection'));
        $this->assertEquals('mysql_usr',  $config['datasources']['mysql']['connection']['user']);
        $this->assertEquals('mysql_pwd',  $config['datasources']['mysql']['connection']['password']);
        $this->assertEquals('mysql_dsn',  $config['datasources']['mysql']['connection']['dsn']);
        $this->assertEquals('mysql',      $config['datasources']['mysql']['adapter']);
    }

    public function testDbalWithMultipleConnectionsAndSettings()
    {
        $container = $this->getContainer();
        $loader = new PropelExtension();

        $config_base = array(
            'path'       => '/propel',
            'phing_path' => '/phing',
        );

        $config_mysql = array(
            'user'      => 'mysql_usr',
            'password'  => 'mysql_pwd',
            'dsn'       => 'mysql_dsn',
            'driver'    => 'mysql',
            'settings'  => array(
                'charset' => array('value' => 'UTF8'),
            ),
        );

        $config_connections = array(
            'default_connection'    => 'mysql',
            'connections'           => array(
                'mysql' => $config_mysql,
        ));

        $configs = array($config_base, array('dbal' => $config_connections));

        $loader->load($configs, $container);

        $arguments = $container->getDefinition('propel.configuration')->getArguments();
        $config = $arguments[0];
        $this->assertEquals('mysql', $container->getParameter('propel.dbal.default_connection'));
        $this->assertEquals('mysql_usr',  $config['datasources']['mysql']['connection']['user']);
        $this->assertEquals('mysql_pwd',  $config['datasources']['mysql']['connection']['password']);
        $this->assertEquals('mysql_dsn',  $config['datasources']['mysql']['connection']['dsn']);

        $this->assertArrayHasKey('settings', $config['datasources']['mysql']['connection']);
        $this->assertArrayHasKey('charset',  $config['datasources']['mysql']['connection']['settings']);
        $this->assertArrayHasKey('value',    $config['datasources']['mysql']['connection']['settings']['charset']);
        $this->assertEquals('UTF8', $config['datasources']['mysql']['connection']['settings']['charset']['value']);
    }

    public function testDbalWithSettings()
    {
        $container = $this->getContainer();
        $loader = new PropelExtension();

        $config_base = array(
            'path'       => '/propel',
            'phing_path' => '/phing',
        );

        $config_mysql = array(
            'user'      => 'mysql_usr',
            'password'  => 'mysql_pwd',
            'dsn'       => 'mysql_dsn',
            'driver'    => 'mysql',
            'settings'  => array(
                'charset' => array('value' => 'UTF8'),
                'queries' => array('query' => 'SET NAMES UTF8')
            ),
        );

        $configs = array($config_base, array('dbal' => $config_mysql));

        $loader->load($configs, $container);

        $arguments = $container->getDefinition('propel.configuration')->getArguments();
        $config = $arguments[0];

        $this->assertArrayHasKey('settings', $config['datasources']['default']['connection']);
        $this->assertArrayHasKey('charset',  $config['datasources']['default']['connection']['settings']);
        $this->assertArrayHasKey('value',    $config['datasources']['default']['connection']['settings']['charset']);
        $this->assertEquals('UTF8', $config['datasources']['default']['connection']['settings']['charset']['value']);

        $this->assertArrayHasKey('settings', $config['datasources']['default']['connection']);
        $this->assertArrayHasKey('queries',  $config['datasources']['default']['connection']['settings']);
        $this->assertArrayHasKey('query',    $config['datasources']['default']['connection']['settings']['queries']);
        $this->assertEquals('SET NAMES UTF8', $config['datasources']['default']['connection']['settings']['queries']['query']);
    }

    public function testDbalWithSlaves()
    {
        $container = $this->getContainer();
        $loader = new PropelExtension();

        $config_base = array(
            'path'       => '/propel',
            'phing_path' => '/phing',
        );

        $config_mysql = array(
            'user'      => 'mysql_usr',
            'password'  => 'mysql_pwd',
            'dsn'       => 'mysql_dsn',
            'driver'    => 'mysql',
            'slaves'  => array(
                'mysql_slave1' => array(
                    'user' => 'mysql_usrs1',
                    'password' => 'mysql_pwds1',
                    'dsn' => 'mysql_dsns1',
                ),
                'mysql_slave2' => array(
                    'user' => 'mysql_usrs2',
                    'password' => 'mysql_pwds2',
                    'dsn' => 'mysql_dsns2',
                ),
            ),
        );

        $configs = array($config_base, array(
            'dbal' => array(
                'default_connection' => 'master',
                'connections'        => array('master' => $config_mysql)
            )
        ));
        $loader->load($configs, $container);

        $arguments = $container->getDefinition('propel.configuration')->getArguments();
        $config = $arguments[0];

        $this->assertArrayHasKey('slaves', $config['datasources']['master']);
        $this->assertArrayHasKey('connection', $config['datasources']['master']['slaves']);
        $this->assertArrayHasKey('mysql_slave1', $config['datasources']['master']['slaves']['connection']);
        $this->assertArrayHasKey('user', $config['datasources']['master']['slaves']['connection']['mysql_slave1']);
        $this->assertArrayHasKey('password', $config['datasources']['master']['slaves']['connection']['mysql_slave1']);
        $this->assertArrayHasKey('dsn', $config['datasources']['master']['slaves']['connection']['mysql_slave1']);
        $this->assertArrayHasKey('mysql_slave2', $config['datasources']['master']['slaves']['connection']);
        $this->assertArrayHasKey('user', $config['datasources']['master']['slaves']['connection']['mysql_slave2']);
        $this->assertArrayHasKey('password', $config['datasources']['master']['slaves']['connection']['mysql_slave2']);
        $this->assertArrayHasKey('dsn', $config['datasources']['master']['slaves']['connection']['mysql_slave2']);

        $this->assertEquals("mysql_usrs1", $config['datasources']['master']['slaves']['connection']['mysql_slave1']['user']);
        $this->assertEquals("mysql_pwds1", $config['datasources']['master']['slaves']['connection']['mysql_slave1']['password']);
        $this->assertEquals("mysql_dsns1", $config['datasources']['master']['slaves']['connection']['mysql_slave1']['dsn']);

        $this->assertEquals("mysql_usrs2", $config['datasources']['master']['slaves']['connection']['mysql_slave2']['user']);
        $this->assertEquals("mysql_pwds2", $config['datasources']['master']['slaves']['connection']['mysql_slave2']['password']);
        $this->assertEquals("mysql_dsns2", $config['datasources']['master']['slaves']['connection']['mysql_slave2']['dsn']);
    }

    public function testDbalWithNoSlaves()
    {
        $container = $this->getContainer();
        $loader = new PropelExtension();

        $config_base = array(
            'path'       => '/propel',
            'phing_path' => '/phing',
        );

        $config_mysql = array(
            'user'      => 'mysql_usr',
            'password'  => 'mysql_pwd',
            'dsn'       => 'mysql_dsn',
            'driver'    => 'mysql'
        );

        $configs = array($config_base, array('dbal' => $config_mysql));
        $loader->load($configs, $container);

        $arguments = $container->getDefinition('propel.configuration')->getArguments();
        $config = $arguments[0];

        $this->assertArrayNotHasKey('slaves', $config['datasources']['default']);
    }

}
