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
use Propel\PropelBundle\Command\AbstractCommand;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class AbstractCommandTest extends TestCase
{
    /**
     * @var TestableAbstractCommand
     */
    protected $command;

    public function setUp()
    {
        $this->command = new TestableAbstractCommand('testable-command');
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

    public function testTransformToLogicalName()
    {
        $bundleDir = realpath(__DIR__ . '/../Fixtures/src/My/SuperBundle');
        $filename = 'Resources' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'a-schema.xml';

        $bundle = $this->getMock('Symfony\Component\HttpKernel\Bundle\BundleInterface');
        $bundle
            ->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('MySuperBundle'));
        $bundle
            ->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue($bundleDir));

        $schema = new \SplFileInfo($bundleDir . DIRECTORY_SEPARATOR . $filename);
        $expected = '@MySuperBundle/Resources/config/a-schema.xml';
        $this->assertEquals($expected, $this->command->transformToLogicalName($schema, $bundle));
    }

    public function testTransformToLogicalNameWithSubDir()
    {
        $bundleDir = realpath(__DIR__ . '/../Fixtures/src/My/ThirdBundle');
        $filename = 'Resources' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'propel' . DIRECTORY_SEPARATOR . 'schema.xml';

        $bundle = $this->getMock('Symfony\Component\HttpKernel\Bundle\BundleInterface');
        $bundle
            ->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('MyThirdBundle'));
        $bundle
            ->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue($bundleDir));

        $schema = new \SplFileInfo($bundleDir . DIRECTORY_SEPARATOR . $filename);
        $expected = '@MyThirdBundle/Resources/config/propel/schema.xml';
        $this->assertEquals($expected, $this->command->transformToLogicalName($schema, $bundle));
    }

    public function testGetSchemasFromBundle()
    {
        $bundle = $this->getMock('Symfony\Component\HttpKernel\Bundle\BundleInterface');
        $bundle
            ->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('MySuperBundle'));
        $bundle
            ->expects($this->exactly(2))
            ->method('getPath')
            ->will($this->returnValue(__DIR__ . '/../Fixtures/src/My/SuperBundle'));

        $aSchema = realpath(__DIR__ . '/../Fixtures/src/My/SuperBundle/Resources/config/a-schema.xml');

        // hack to by pass the file locator
        $this->command->setLocateResponse($aSchema);

        $schemas = $this->command->getSchemasFromBundle($bundle);

        $this->assertNotNull($schemas);
        $this->assertTrue(is_array($schemas));
        $this->assertCount(1, $schemas);
        $this->assertArrayHasKey($aSchema, $schemas);
        $this->assertSame($bundle, $schemas[$aSchema][0]);
        $this->assertEquals(new \SplFileInfo($aSchema), $schemas[$aSchema][1]);
    }

    public function testGetSchemasFromBundleWithNoSchema()
    {
        $bundle = $this->getMock('Symfony\Component\HttpKernel\Bundle\BundleInterface');
        $bundle
            ->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue(__DIR__ . '/../Fixtures/src/My/SecondBundle'));

        $schemas = $this->command->getSchemasFromBundle($bundle);

        $this->assertNotNull($schemas);
        $this->assertTrue(is_array($schemas));
        $this->assertCount(0, $schemas);
    }

    public function testGetFinalSchemasWithNoSchemaInBundles()
    {
        $bundle = $this->getMock('Symfony\Component\HttpKernel\Bundle\BundleInterface');
        $kernel = $this->getMock('Symfony\Component\HttpKernel\KernelInterface');

        $bundle
            ->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue(__DIR__ . '/../Fixtures/src/My/SecondBundle'));

        $kernel
            ->expects($this->once())
            ->method('getBundles')
            ->will($this->returnValue(array($bundle)));

        $schemas = $this->command->getFinalSchemas($kernel);

        $this->assertNotNull($schemas);
        $this->assertTrue(is_array($schemas));
        $this->assertCount(0, $schemas);
    }

    public function testGetFinalSchemas()
    {
        $bundle = $this->getMock('Symfony\Component\HttpKernel\Bundle\BundleInterface');
        $kernel = $this->getMock('Symfony\Component\HttpKernel\KernelInterface');

        $bundle
            ->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('MySuperBundle'));
        $bundle
            ->expects($this->exactly(2))
            ->method('getPath')
            ->will($this->returnValue(__DIR__ . '/../Fixtures/src/My/SuperBundle'));

        $aSchema = realpath(__DIR__ . '/../Fixtures/src/My/SuperBundle/Resources/config/a-schema.xml');

        // hack to by pass the file locator
        $this->command->setLocateResponse($aSchema);

        $kernel
            ->expects($this->once())
            ->method('getBundles')
            ->will($this->returnValue(array($bundle)));

        $schemas = $this->command->getFinalSchemas($kernel);

        $this->assertNotNull($schemas);
        $this->assertTrue(is_array($schemas));
        $this->assertCount(1, $schemas);
        $this->assertArrayHasKey($aSchema, $schemas);
        $this->assertSame($bundle, $schemas[$aSchema][0]);
        $this->assertEquals(new \SplFileInfo($aSchema), $schemas[$aSchema][1]);
    }

    public function testGetFinalSchemasWithGivenBundle()
    {
        $bundle = $this->getMock('Symfony\Component\HttpKernel\Bundle\BundleInterface');
        $kernel = $this->getMock('Symfony\Component\HttpKernel\KernelInterface');

        $bundle
            ->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('MySuperBundle'));
        $bundle
            ->expects($this->exactly(2))
            ->method('getPath')
            ->will($this->returnValue(__DIR__ . '/../Fixtures/src/My/SuperBundle'));

        $aSchema = realpath(__DIR__ . '/../Fixtures/src/My/SuperBundle/Resources/config/a-schema.xml');

        // hack to by pass the file locator
        $this->command->setLocateResponse($aSchema);

        $kernel
            ->expects($this->never())
            ->method('getBundles');

        $schemas = $this->command->getFinalSchemas($kernel, $bundle);

        $this->assertNotNull($schemas);
        $this->assertTrue(is_array($schemas));
        $this->assertCount(1, $schemas);
        $this->assertArrayHasKey($aSchema, $schemas);
        $this->assertSame($bundle, $schemas[$aSchema][0]);
        $this->assertEquals(new \SplFileInfo($aSchema), $schemas[$aSchema][1]);
    }
}

class TestableAbstractCommand extends AbstractCommand
{
    private $locate;

    public function setLocateResponse($locate)
    {
        $this->locate = $locate;
    }

    public function getContainer()
    {
        return $this;
    }

    public function get($service)
    {
        return $this;
    }

    public function locate($file)
    {
        return $this->locate;
    }

    public function parseDbName($dsn)
    {
        return parent::parseDbName($dsn);
    }

    public function transformToLogicalName(\SplFileInfo $schema, BundleInterface $bundle)
    {
        return parent::transformToLogicalName($schema, $bundle);
    }

    public function getSchemasFromBundle(BundleInterface $bundle)
    {
        return parent::getSchemasFromBundle($bundle);
    }

    public function getFinalSchemas(KernelInterface $kernel, BundleInterface $bundle = null)
    {
        return parent::getFinalSchemas($kernel, $bundle);
    }
}
