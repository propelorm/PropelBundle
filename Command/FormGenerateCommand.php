<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Command;

use Propel\Bundle\PropelBundle\Form\FormBuilder;
use Propel\Generator\Config\GeneratorConfig;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Table;
use Propel\Generator\Manager\ModelManager;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * @author William DURAND <william.durand1@gmail.com>
 */
class FormGenerateCommand extends AbstractCommand
{
    const DEFAULT_FORM_TYPE_DIRECTORY = '/Form/Type';
    
    use BundleTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('propel:form:generate')
            ->setDescription('Generate Form types stubs based on the schema.xml')

            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite existing Form types')
            ->addOption('platform',  null, InputOption::VALUE_REQUIRED,  'The platform')
            ->addArgument('bundle', InputArgument::OPTIONAL, 'The bundle to use to generate Form types (Ex: @AcmeDemoBundle)')
            ->addArgument('models', InputArgument::IS_ARRAY, 'Model classes to generate Form Types from')

            ->setHelp(<<<EOT
The <info>%command.name%</info> command allows you to quickly generate Form Type stubs for a given bundle.

<info>php app/console %command.full_name%</info>

The <info>--force</info> parameter allows you to overwrite existing files.
EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $kernel = $this->getApplication()->getKernel();
        $models = $input->getArgument('models');
        $force = $input->getOption('force');

        $bundle = $this->getBundle($input, $output);

        $this->setupBuildTimeFiles();
        $schemas = $this->getFinalSchemas($kernel, $bundle);
        if (!$schemas) {
            $output->writeln(sprintf('No <comment>*schemas.xml</comment> files found in bundle <comment>%s</comment>.', $bundle->getName()));
            return;
        }

        $manager = $this->getModelManager($input, $schemas);

        foreach ($manager->getDataModels() as $dataModel) {
            foreach ($dataModel->getDatabases() as $database) {
                $this->createFormTypeFromDatabase($bundle, $database, $models, $output, $force);
            }
        }
    }

    /**
     * Create FormTypes from a given database, bundle and models.
     *
     * @param BundleInterface $bundle   The bundle for which the FormTypes will be generated.
     * @param Database        $database The database to inspect.
     * @param array           $models   The models to build.
     * @param OutputInterface $output   An OutputInterface instance
     * @param boolean         $force    Override files if present.
     */
    protected function createFormTypeFromDatabase(BundleInterface $bundle, Database $database, $models, OutputInterface $output, $force = false)
    {
        $dir = $this->createDirectory($bundle, $output);

        foreach ($database->getTables() as $table) {
            if (0 < count($models) && !in_array($table->getPhpName(), $models)) {
                continue;
            }

            $file = new \SplFileInfo(sprintf('%s/%sType.php', $dir, $table->getPhpName()));

            if (!file_exists($file) || true === $force) {
                $this->writeFormType($bundle, $table, $file, $force, $output);
            } else {
                $output->writeln(sprintf('File <comment>%-60s</comment> exists, skipped. Try the <info>--force</info> option.', $this->getRelativeFileName($file)));
            }
        }
    }

    /**
     * Create the FormType directory and log the result.
     *
     * @param BundleInterface $bundle The bundle in which we'll create the directory.
     * @param OutputInterface $output An OutputInterface instance.
     *
     * @return string The path to the created directory.
     */
    protected function createDirectory(BundleInterface $bundle, OutputInterface $output)
    {
        $fs = new Filesystem();

        if (!$fs->exists($dir = $bundle->getPath() . self::DEFAULT_FORM_TYPE_DIRECTORY)) {
            $fs->mkdir($dir);
            $this->writeNewDirectory($output, $dir);
        }

        return $dir;
    }

    /**
     * Write a FormType.
     *
     * @param BundleInterface $bundle The bundle in which the FormType will be created.
     * @param Table           $table  The table for which the FormType will be created.
     * @param \SplFileInfo    $file   File representing the FormType.
     * @param boolean         $force  Is the write forced?
     * @param OutputInterface $output An OutputInterface instance.
     */
    protected function writeFormType(BundleInterface $bundle, Table $table, \SplFileInfo $file, $force, OutputInterface $output)
    {
        $formBuilder = new FormBuilder();
        $formTypeContent = $formBuilder->buildFormType($bundle, $table, self::DEFAULT_FORM_TYPE_DIRECTORY);

        file_put_contents($file->getPathName(), $formTypeContent);
        $this->writeNewFile($output, $this->getRelativeFileName($file) . ($force ? ' (forced)' : ''));
    }

    /**
     * @param  \SplFileInfo $file
     * @return string
     */
    protected function getRelativeFileName(\SplFileInfo $file)
    {
        return substr(str_replace(realpath($this->getContainer()->getParameter('kernel.root_dir') . '/../'), '', $file), 1);
    }

    /**
     * Get the GeneratorConfig instance to use.
     *
     * @param InputInterface $input An InputInterface instance.
     *
     * @return GeneratorConfig
     */
    protected function getGeneratorConfig(InputInterface $input)
    {
        $generatorConfig = null;

        if (null !== $input->getOption('platform')) {
            $generatorConfig['propel']['generator']['platformClass'] = $input->getOption('platform');
        }

        return new GeneratorConfig($this->getCacheDir().'/propel.json', $generatorConfig);
    }

    /**
     * Get the ModelManager to use.
     *
     * @param InputInterface $input   An InputInterface instance.
     * @param array          $schemas A list of schemas.
     *
     * @return ModelManager
     */
    protected function getModelManager(InputInterface $input, array $schemas)
    {
        $schemaFiles = array();
        foreach ($schemas as $data) {
            $schemaFiles[] = $data[1];
        }

        $manager = new ModelManager();
        $manager->setFilesystem(new Filesystem());
        $manager->setGeneratorConfig($this->getGeneratorConfig($input));
        $manager->setSchemas($schemaFiles);

        return $manager;
    }
}
