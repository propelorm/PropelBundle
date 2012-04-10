<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * @author William DURAND <william.durand1@gmail.com>
 */
class FormGenerateCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDescription('Generate Form types stubs based on the schema.xml')
            ->addArgument('bundle', InputArgument::REQUIRED, 'The bundle to use to generate Form types')
            ->setHelp('')
            ->setName('propel:form:generate');
    }

    /**
     * @see Command
     *
     * @throws \InvalidArgumentException When the target directory does not exist
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $propelPath = $this->getContainer()->getParameter('propel.path');
        require_once sprintf('%s/generator/lib/builder/util/XmlToAppData.php', $propelPath);
        require_once sprintf('%s/generator/lib/config/GeneratorConfig.php', $propelPath);
        require_once sprintf('%s/generator/lib/config/QuickGeneratorConfig.php', $propelPath);

        set_include_path(sprintf('%s/generator/lib', $propelPath) . PATH_SEPARATOR . get_include_path());

        if ('@' === substr($input->getArgument('bundle'), 0, 1)) {
            $bundle = $this
                ->getContainer()
                ->get('kernel')
                ->getBundle(substr($input->getArgument('bundle'), 1));

            if (is_dir($dir = $bundle->getPath().'/Resources/config')) {
                $finder  = new Finder();
                $schemas = $finder->files()->name('*schema.xml')->followLinks()->in($dir);

                $array = array();
                foreach ($schemas as $schema) {
                    $array[] = $schema->getPathName();
                }
            }

            $transformer = new \XmlToAppData(null, null, 'UTF-8');
            $transformer->setGeneratorConfig(new \QuickGeneratorConfig());

            $appDatas = array();
            foreach ($array as $xmlFile) {
                $appDatas[] = $transformer->parseFile($xmlFile);
            }

            var_dump($appDatas);
        }
    }
}
