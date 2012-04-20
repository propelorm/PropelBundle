<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
abstract class GeneratorAwareCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->loadPropelGenerator();
    }

    protected function loadPropelGenerator()
    {
        $propelPath = $this->getContainer()->getParameter('propel.path');

        require_once sprintf('%s/generator/lib/builder/util/XmlToAppData.php',   $propelPath);
        require_once sprintf('%s/generator/lib/config/GeneratorConfig.php',      $propelPath);
        require_once sprintf('%s/generator/lib/config/QuickGeneratorConfig.php', $propelPath);

        set_include_path(sprintf('%s/generator/lib', $propelPath) . PATH_SEPARATOR . get_include_path());
    }

    protected function getDatabasesFromSchema(\SplFileInfo $file)
    {
        $transformer = new \XmlToAppData(null, null, 'UTF-8');
        $config      = new \QuickGeneratorConfig();

        if (file_exists($propelIni = $this->getContainer()->getParameter('kernel.root_dir') . '/config/propel.ini')) {
            foreach ($this->getProperties($propelIni) as $key => $value) {
                if (0 === strpos($key, 'propel.')) {
                    $newKey = substr($key, strlen('propel.'));

                    $j = strpos($newKey, '.');
                    while (false !== $j) {
                        $newKey = substr($newKey, 0, $j) . ucfirst(substr($newKey, $j + 1));
                        $j = strpos($newKey, '.');
                    }

                    $config->setBuildProperty($newKey, $value);
                }
            }
        }

        $transformer->setGeneratorConfig($config);

        return $transformer->parseFile($file->getPathName())->getDatabases();
    }
}
