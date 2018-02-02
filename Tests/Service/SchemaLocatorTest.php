<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Tests\Service;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Propel\Bundle\PropelBundle\Service\SchemaLocator;
use Propel\Bundle\PropelBundle\Tests\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;

class SchemaLocatorTest extends TestCase
{
    /**
     * @var Kernel
     */
    private $kernelMock;

    /**
     * @var vfsStreamDirectory
     */
    private $root;

    /**
     * @var array
     */
    private $configuration;

    public function setUp()
    {
        $pathStructure = [
                'schema' => [
                    'first.schema.xml' => 'First database schema',
                    'second.schema.xml' => 'Second database schema'
                ],
                'configuration' => [
                    'directory' => [
                        'schema.xml' => 'Schema from configuration'
                    ]
                ]
        ];
        $this->root = vfsStream::setup('projectDir');
        vfsStream::create($pathStructure);

        $this->kernelMock = $this->getMockBuilder(Kernel::class)->disableOriginalConstructor()-> getMock();
        $this->kernelMock->method('getProjectDir')->willReturn($this->root->url());

        $this->configuration['paths']['schemaDir'] = vfsStream::url('projectDir/configuration/directory');
    }

    public function testLocateFromProject()
    {
        $locator = new SchemaLocator($this->configuration);
        $files = $locator->locateFromProject($this->kernelMock);

        $this->assertCount(2, $files);
        $this->assertTrue(isset($files['vfs://projectDir/schema/first.schema.xml']));
        $this->assertEquals('first.schema.xml', $files['vfs://projectDir/schema/first.schema.xml']->getFileName());
        $this->assertTrue(isset($files['vfs://projectDir/schema/second.schema.xml']));
        $this->assertEquals('second.schema.xml', $files['vfs://projectDir/schema/second.schema.xml']->getFileName());
    }

    public function testLocateFromProjectAndConfiguration()
    {
        $locator = new SchemaLocator($this->configuration);
        $files = $locator->locateFromProjectAndConfiguration($this->kernelMock);

        $this->assertCount(3, $files);
        $this->assertTrue(isset($files['vfs://projectDir/schema/first.schema.xml']));
        $this->assertEquals('first.schema.xml', $files['vfs://projectDir/schema/first.schema.xml']->getFileName());
        $this->assertTrue(isset($files['vfs://projectDir/schema/second.schema.xml']));
        $this->assertEquals('second.schema.xml', $files['vfs://projectDir/schema/second.schema.xml']->getFileName());
        $this->assertTrue(isset($files['vfs://projectDir/configuration/directory/schema.xml']));
        $this->assertEquals('schema.xml', $files['vfs://projectDir/configuration/directory/schema.xml']->getFileName());
    }
}
