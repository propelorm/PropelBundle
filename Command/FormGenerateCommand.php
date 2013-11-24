<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Command;

use Propel\Generator\Config\GeneratorConfig;
use Propel\Generator\Command\ModelBuildCommand;
use Propel\Generator\Command\AbstractCommand as BaseCommand;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Table;
use Propel\Generator\Manager\ModelManager;
use Propel\Runtime\Propel;

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

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('propel:form:generate')
            ->setDescription('Generate Form types stubs based on the schema.xml')

            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite existing Form types')
            ->addOption('platform',  null, InputOption::VALUE_REQUIRED,  'The platform', BaseCommand::DEFAULT_PLATFORM)
            ->addArgument('bundle', InputArgument::REQUIRED, 'The bundle to use to generate Form types')
            ->addArgument('models', InputArgument::IS_ARRAY, 'Model classes to generate Form Types from')

            ->setHelp(<<<EOT
The <info>%command.name%</info> command allows you to quickly generate Form Type stubs for a given bundle.

<info>php app/console %command.full_name%</info>

The <info>--force</info> parameter allows you to overwrite existing files.
EOT
        );
    }

    /**
     * @see Command
     *
     * @throws \InvalidArgumentException When the target directory does not exist
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $kernel = $this->getApplication()->getKernel();
        $models = $input->getArgument('models');
        $force = $input->getOption('force');

        $this->setupBuildTimeFiles();

        if (!($schemas = $this->getFinalSchemas($kernel, $this->bundle))) {
            $output->writeln(sprintf('No <comment>*schemas.xml</comment> files found in bundle <comment>%s</comment>.', $this->bundle->getName()));

            return;
        }

        $manager = $this->getModelManager($input, $schemas);

        foreach ($manager->getDataModels() as $dataModel) {
            foreach ($dataModel->getDatabases() as $database) {
                $this->createFormTypeFromDatabase($this->bundle, $database, $models, $output, $force);
            }
        }
    }

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

    protected function createDirectory(BundleInterface $bundle, OutputInterface $output)
    {
        $fs = new Filesystem();

        if (!$fs->exists($dir = $bundle->getPath() . self::DEFAULT_FORM_TYPE_DIRECTORY)) {
            $fs->mkdir($dir);
            $this->writeNewDirectory($output, $dir);
        }

        return $dir;
    }

    protected function writeFormType(BundleInterface $bundle, Table $table, \SplFileInfo $file, $force, OutputInterface $output)
    {
        $modelName = $table->getPhpName();
        $formTypeContent = file_get_contents(__DIR__ . '/../Resources/skeleton/FormType.php');

        $formTypeContent = str_replace('##NAMESPACE##', $bundle->getNamespace() . str_replace('/', '\\', self::DEFAULT_FORM_TYPE_DIRECTORY), $formTypeContent);
        $formTypeContent = str_replace('##CLASS##', $modelName . 'Type', $formTypeContent);
        $formTypeContent = str_replace('##FQCN##', sprintf('%s\%s', $table->getNamespace(), $modelName), $formTypeContent);
        $formTypeContent = str_replace('##TYPE_NAME##', strtolower($modelName), $formTypeContent);
        $formTypeContent = $this->addFields($table, $formTypeContent);

        file_put_contents($file->getPathName(), $formTypeContent);
        $this->writeNewFile($output, $this->getRelativeFileName($file) . ($force ? ' (forced)' : ''));
    }

    protected function addFields(Table $table, $formTypeContent)
    {
        $buildCode = '';
        foreach ($table->getColumns() as $column) {
            if (!$column->isPrimaryKey()) {
                $buildCode .= sprintf("\n        \$builder->add('%s');", lcfirst($column->getPhpName()));
            }
        }

        return str_replace('##BUILD_CODE##', $buildCode, $formTypeContent);
    }

    /**
     * @param  \SplFileInfo $file
     * @return string
     */
    protected function getRelativeFileName(\SplFileInfo $file)
    {
        return substr(str_replace(realpath($this->getContainer()->getParameter('kernel.root_dir') . '/../'), '', $file), 1);
    }

    protected function getGeneratorConfig(InputInterface $input)
    {
        $generatorConfig = array(
            'propel.platform.class'                     => $input->getOption('platform'),
            'propel.builder.object.class'               => ModelBuildCommand::DEFAULT_OBJECT_BUILDER,
            'propel.builder.objectstub.class'           => ModelBuildCommand::DEFAULT_OBJECT_STUB_BUILDER,
            'propel.builder.objectmultiextend.class'    => ModelBuildCommand::DEFAULT_MULTIEXTEND_OBJECT_BUILDER,
            'propel.builder.query.class'                => ModelBuildCommand::DEFAULT_QUERY_BUILDER,
            'propel.builder.querystub.class'            => ModelBuildCommand::DEFAULT_QUERY_STUB_BUILDER,
            'propel.builder.queryinheritance.class'     => ModelBuildCommand::DEFAULT_QUERY_INHERITANCE_BUILDER,
            'propel.builder.queryinheritancestub.class' => ModelBuildCommand::DEFAULT_QUERY_INHERITANCE_STUB_BUILDER,
            'propel.builder.tablemap.class'             => ModelBuildCommand::DEFAULT_TABLEMAP_BUILDER,
            'propel.builder.pluralizer.class'           => ModelBuildCommand::DEFAULT_PLURALIZER,
            'propel.disableIdentifierQuoting'           => !false,
            'propel.packageObjectModel'                 => true,
            'propel.namespace.autoPackage'              => !false,
            'propel.addGenericAccessors'                => true,
            'propel.addGenericMutators'                 => true,
            'propel.addSaveMethod'                      => true,
            'propel.addTimeStamp'                       => false,
            'propel.addValidateMethod'                  => true,
            'propel.addHooks'                           => true,
            'propel.namespace.map'                      => 'Map',
            'propel.useLeftJoinsInDoJoinMethods'        => true,
            'propel.emulateForeignKeyConstraints'       => false,
            'propel.schema.autoPrefix'                  => false,
            'propel.dateTimeClass'                      => '\DateTime',
            // MySQL specific
            'propel.mysql.tableType'                    => ModelBuildCommand::DEFAULT_MYSQL_ENGINE,
            'propel.mysql.tableEngineKeyword'           => 'ENGINE',
        );

        // merge the custom build properties
        $buildProperties = parse_ini_file($this->getCacheDir().'/build.properties');
        $generatorConfig = array_merge($generatorConfig, $buildProperties);

        return new GeneratorConfig($generatorConfig);
    }

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
