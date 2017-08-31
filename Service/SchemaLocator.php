<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Service;

use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class SchemaLocator
{
    protected $fileLocator;
    protected $configuration;

    public function __construct(FileLocatorInterface $fileLocator, array $configuration)
    {
        $this->fileLocator = $fileLocator;
        $this->configuration = $configuration;
    }

    public function locateFromBundlesAndConfiguration(array $bundles)
    {
        $schemas = $this->locateFromBundles($bundles);

        $path = $this->configuration['paths']['schemaDir'].'/schema.xml';
        if (file_exists($path)) {
            $schema = new \SplFileInfo($path);
            $schemas[(string) $schema] = array(null, $schema);
        }

        return $schemas;
    }

    public function locateFromBundles(array $bundles)
    {
        $schemas = array();
        foreach ($bundles as $bundle) {
            $schemas = array_merge($schemas, $this->locateFromBundle($bundle));
        }

        return $schemas;
    }

    /**
     * @param \Symfony\Component\HttpKernel\Bundle\BundleInterface
     */
    public function locateFromBundle(BundleInterface $bundle)
    {
        $finalSchemas = array();

        if (is_dir($dir = $bundle->getPath().'/Resources/config')) {
            $finder  = new Finder();
            $schemas = $finder->files()->name('*schema.xml')->followLinks()->in($dir);

            if (iterator_count($schemas)) {
                foreach ($schemas as $schema) {
                    $logicalName = $this->transformToLogicalName($schema, $bundle);
                    $finalSchema = new \SplFileInfo($this->fileLocator->locate($logicalName));

                    $finalSchemas[(string) $finalSchema] = array($bundle, $finalSchema);
                }
            }
        }

        return $finalSchemas;
    }

    /**
     * @param  \SplFileInfo    $schema
     * @param  BundleInterface $bundle
     * @return string
     */
    protected function transformToLogicalName(\SplFileInfo $schema, BundleInterface $bundle)
    {
        $schemaPath = str_replace(
            $bundle->getPath(). DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR,
            '',
            $schema->getRealPath()
        );

        return sprintf('@%s/Resources/config/%s', $bundle->getName(), $schemaPath);
    }
}
