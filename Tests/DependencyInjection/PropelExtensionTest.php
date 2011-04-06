<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
                'path'       => '/propel',
                'phing_path' => '/phing',
            )), $container);
            $this->fail();
        } catch (\Exception $e) {
            $this->assertInstanceOf('InvalidArgumentException', $e,
                '->load() throws an \InvalidArgumentException if a "dbal" configuration is not set.');
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
            'path' => '/propel',
            'phing_path' => '/phing',
            'dbal' => array()
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

    public function testDbalLoadCascade() {
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

    public function testDbalLoadMultipleConnections() {
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
}
