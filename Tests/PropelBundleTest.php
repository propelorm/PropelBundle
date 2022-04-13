<?php
/** *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Tests;

use Propel\Bundle\PropelBundle\Logger\PropelLogger;
use Propel\Bundle\PropelBundle\PropelBundle;
use Propel\Bundle\PropelBundle\Tests\TestCase;
use Propel\Common\Config\ConfigurationManager;
use Propel\Common\Config\FileLocator;
use Propel\Common\Config\Loader\IniFileLoader;
use Propel\Generator\Util\VfsTrait;
use Propel\Runtime\Adapter\Pdo\SqliteAdapter;
use Propel\Runtime\Connection\ConnectionManagerPrimaryReplica;
use Propel\Runtime\Connection\ConnectionManagerSingle;
use Propel\Runtime\Connection\ConnectionWrapper;
use Propel\Runtime\Propel;
use Propel\Runtime\ServiceContainer\StandardServiceContainer;
use Psr\Log\NullLogger;
use Symfony\Component\HttpKernel\DependencyInjection\LoggerPass;

/**
 * @author SkyFoxvn
 * NOTE: this class test only changes made by this bundle which make change on propel bundle
 */
class PropelBundleTest extends TestCase
{
    use VfsTrait;

    /**
     * cant be tested with current implementation because it load PropelExtension which look for file
     * @NOTE: need refactoring
     * @return void
     */
//    public function testExtension() {
//        // set container parameters
//        $container = $this->getContainer();
//        $container->setParameter('propel.configuration', array(
//            //'security' => 'asd'
//            'database' => [
//                'connections' => [
//                    'mysource' => ['dsn' => 'sqlite::memory:', 'adapter' => 'sqlite']
//                ]
//            ]
//        ));
//
//        // init PropelBundle
//        $propelBundleClass = new PropelBundle();
//        $propelBundleClass->setContainer($container);
//        $propelBundleClass->boot();
//
//        // load extension
//        $container->registerExtension($propelBundleClass->getContainerExtension());
//        $container->loadFromExtension('propel');
//
//        // compile
//        $container->compile();
//
////        var_dump($container->getParameter('propel.configuration'));
////        var_dump($container->getExtensions());
//
//        $this->assertTrue($container->hasExtension('security'));
//    }

    public function testConfigureConnections() {
        /**
         * set single connection without slaves
         */
        // reset service container to ensure no connections exists
        Propel::setServiceContainer(new StandardServiceContainer());
        $serviceContainer = Propel::getServiceContainer();

        // init PropelBundle class
        $this->initPropelBundle(array(
            'paths' => ['loaderScriptDir' => 'not_exists'],
            'database' => [
                'connections' => [
                    'mysource' => ['dsn' => 'sqlite::memory:', 'adapter' => 'sqlite']
                ]
            ],
            'runtime' => [
                'defaultConnection' => 'test_default_connection'
            ]
        ));

        // tests
        $manager = $serviceContainer->getConnectionManager('mysource');

        $this->assertInstanceOf(ConnectionManagerSingle::class, $manager);
        $this->assertSame($manager->getReadConnection(new SqliteAdapter()), $manager->getWriteConnection(new SqliteAdapter()));
        $this->assertEquals('sqlite', $serviceContainer->getAdapterClass('mysource'));
        $this->assertEquals('test_default_connection', $serviceContainer->getDefaultDatasource());

        /**
         * set single connection with slaves
         */
        // reset service container to ensure no connections exists
        Propel::setServiceContainer(new StandardServiceContainer());
        $serviceContainer = Propel::getServiceContainer();

        // init PropelBundle class
        $this->initPropelBundle(array(
            'paths' => ['loaderScriptDir' => 'not_exists'],
            'database' => [
                'connections' => [
                    'mysource2' => [
                        'dsn' => 'sqlite::memory:',
                        'adapter' => 'sqlite',
                        'slaves' => [
                            ['dsn' => 'sqlite::memory:']
                        ]
                    ]
                ]
            ],
            'runtime' => [
                'defaultConnection' => 'test_default_connection2'
            ]
        ));

        // tests
        $manager = $serviceContainer->getConnectionManager('mysource2');

        $this->assertInstanceOf(ConnectionManagerPrimaryReplica::class, $manager);
        $this->assertEquals('sqlite', $serviceContainer->getAdapterClass('mysource2'));
        $this->assertEquals('test_default_connection2', $serviceContainer->getDefaultDatasource());
        // master slave must be different
        $this->assertNotSame($manager->getWriteConnection(new SqliteAdapter()), $manager->getReadConnection(new SqliteAdapter()));

        /**
         * multiple connections with slave
         */
        // reset service container to ensure no connections exists
        Propel::setServiceContainer(new StandardServiceContainer());
        $serviceContainer = Propel::getServiceContainer();

        // init PropelBundle class
        $this->initPropelBundle(array(
            'paths' => ['loaderScriptDir' => 'not_exists'],
            'database' => [
                'connections' => [
                    'mysource3' => [
                        'dsn' => 'sqlite::memory:',
                        'adapter' => 'sqlite',
                        'slaves' => [
                            ['dsn' => 'sqlite::memory:']
                        ]
                    ],
                    'mysource4' => [
                        'dsn' => 'sqlite::memory:',
                        'adapter' => 'sqlite'
                    ],
                ]
            ],
            'runtime' => [
                'defaultConnection' => 'test_default_connection3'
            ]
        ));

        // tests
        $this->assertCount(2, $serviceContainer->getConnectionManagers());
        // connection 1
        $this->assertInstanceOf(ConnectionManagerPrimaryReplica::class, $serviceContainer->getConnectionManager('mysource3'));
        // connection 2
        $this->assertInstanceOf(ConnectionManagerSingle::class, $serviceContainer->getConnectionManager('mysource4'));
    }

    /**
     * test logger is set and methods exist
     * @return void
     */
    public function testConfigureLogging() {
        Propel::setServiceContainer(new StandardServiceContainer());
        $serviceContainer = Propel::getServiceContainer();

        // ensure no logger is set at the start
        $this->assertInstanceOf(NullLogger::class, $serviceContainer->getLogger());

        // init PropelBundle class
        $container = $this->initPropelBundle(array(
            'paths' => ['loaderScriptDir' => 'not_exists'],
            'database' => [
                'connections' => [
                    'mysource1' => [
                        'dsn' => 'sqlite::memory:',
                        'adapter' => 'sqlite',
                        'slaves' => [
                            ['dsn' => 'sqlite::memory:']
                        ]
                    ],
                    'mysource2' => [
                        'dsn' => 'sqlite::memory:',
                        'adapter' => 'sqlite'
                    ],
                ]
            ],
            'runtime' => [
                'defaultConnection' => 'test_default_connection'
            ]
        ), new PropelLogger());

        // logger
        $this->assertInstanceOf(PropelLogger::class, $serviceContainer->getLogger());
        // conn 1
        $con1 = $serviceContainer->getConnectionManager('mysource1');
        $this->assertTrue(in_array('prepare', $con1->getReadConnection()->getLogMethods()));
        $this->assertTrue(in_array('prepare', $con1->getReadConnection()->getLogMethods()));
        // conn 2
        $con2 = $serviceContainer->getConnectionManager('mysource2');
        $this->assertTrue(in_array('prepare', $con2->getReadConnection()->getLogMethods()));
        $this->assertTrue(in_array('prepare', $con2->getReadConnection()->getLogMethods()));
    }

    /**
     * initialize PropelBundle with given configuration
     * @param array $configuration propel configuration parameters
     * @return \Symfony\Component\DependencyInjection\ContainerBuilder
     */
    private function initPropelBundle(array $configuration, $logger = false) {
        // init new container
        $container = $this->getContainer();

        // logger optional
        if ($logger !== false) {
            $container->set('propel.logger', $logger);
        }
        // required to be set value dosant really mater
        $container->setParameter('propel.logging', (bool)$logger);

        // set config parameters
        $container->setParameter('propel.configuration', $configuration);

        // init PropelBundle
        $propelBundleClass = new PropelBundle();
        $propelBundleClass->setContainer($container);
        $propelBundleClass->boot();

        return $container;
    }
}