<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Service;

use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;

class SchemaLocator
{
    /**
     * @var array Configuration array
     */
    protected $configuration;

    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Locate the schema files from the project default directory (`/schema`) of from the
     * directory taken from the configuration.
     *
     * @param KernelInterface $kernel
     *
     * @return array
     */
    public function locateFromProjectAndConfiguration(KernelInterface $kernel)
    {
        $projectSchemas = $this->locateFromProject($kernel);
        $confSchemas = $this->locateFromDir($this->configuration['paths']['schemaDir']);

        return array_merge($projectSchemas, $confSchemas);
    }

    /**
     * Locate the schemas from the project directory.
     *
     * @param KernelInterface $kernel
     *
     * @return array
     */
    public function locateFromProject(KernelInterface $kernel)
    {
        return $this->locateFromDir($kernel->getProjectDir().'/schema');
    }

    /**
     * Locate all the schema files into a given directory.
     *
     * @param string $dir The directory to search in
     *
     * @return array
     */
    private function locateFromDir($dir = '')
    {
        $finalSchemas = array();

        if (is_dir($dir)) {
            $finder  = new Finder();
            $schemas = $finder->files()->name('*schema.xml')->followLinks()->in($dir);

            if (iterator_count($schemas)) {
                foreach ($schemas as $schema) {
                    $finalSchemas[(string) $schema] = $schema;
                }
            }
        }

        return $finalSchemas;
    }
}
