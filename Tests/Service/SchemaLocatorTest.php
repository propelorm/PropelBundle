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
use PHPUnit\Framework\MockObject\MockObject;
use Propel\Bundle\PropelBundle\Service\SchemaLocator;
use Propel\Bundle\PropelBundle\Tests\Fixtures\FakeBundle\FakeBundle;
use Propel\Bundle\PropelBundle\Tests\TestCase;
use Propel\Common\Config\FileLocator;
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

    private $fileLocator;

    /**
     * @var MockObject
     */
    private $bundleMock;

    public function setUp()
    {
        $pathStructure = [
                'configuration' => [
                    'directory' => [
                        'schema.xml' => 'Schema from configuration'
                    ]
                ],
        ];
        $this->root = vfsStream::setup('projectDir');
        vfsStream::create($pathStructure);

        $this->kernelMock = $this->getMockBuilder(Kernel::class)->disableOriginalConstructor()-> getMock();
        $this->kernelMock->method('getProjectDir')->willReturn($this->root->url());

        $this->bundleMock = new FakeBundle();

        $this->configuration['paths']['schemaDir'] = vfsStream::url('projectDir/configuration/directory');
        $this->fileLocator = new FileLocator(
            [
                __DIR__ . '/../Fixtures',
            ]
        );
    }

    public function testLocateFromBundle()
    {

        $locator = new SchemaLocator($this->fileLocator, $this->configuration);
        $files = $locator->locateFromBundle($this->bundleMock);

        $this->assertCount(1, $files);
        $this->assertTrue(isset($files[__DIR__ . '/../Fixtures/FakeBundle/Resources/config/bundle.schema.xml']));
        $this->assertEquals('bundle.schema.xml', $files[__DIR__ . '/../Fixtures/FakeBundle/Resources/config/bundle.schema.xml'][1]->getFileName());

    }

    public function testLocateFromBundlesAndConfiguration()
    {
        $locator = new SchemaLocator($this->fileLocator, $this->configuration);
        $files = $locator->locateFromBundlesAndConfiguration(
            [$this->bundleMock]
        );

        $this->assertCount(2, $files);
        $this->assertTrue(isset($files[__DIR__ . '/../Fixtures/FakeBundle/Resources/config/bundle.schema.xml']));
        $this->assertEquals('bundle.schema.xml', $files[__DIR__ . '/../Fixtures/FakeBundle/Resources/config/bundle.schema.xml'][1]->getFileName());
        $this->assertTrue(isset($files['vfs://projectDir/configuration/directory/schema.xml']));
        $this->assertEquals('schema.xml', $files['vfs://projectDir/configuration/directory/schema.xml'][1]->getFileName());

    }
}
