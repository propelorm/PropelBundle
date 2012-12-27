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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * @author William DURAND <william.durand1@gmail.com>
 */
class FormGenerateCommand extends GeneratorAwareCommand
{
    const DEFAULT_FORM_TYPE_DIRECTORY = '/Form/Type';

    /** @var InputInterface */
    private $_input;

    /** @var OutputInterface */
    private $_output;

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDescription('Generate Form types stubs based on the schema.xml')
            ->addArgument('bundle', InputArgument::REQUIRED, 'The bundle to use to generate Form types')
            ->addArgument('models', InputArgument::IS_ARRAY, 'Model classes to generate Form Types from')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite existing Form types')
            ->addOption('originName', 'o', InputOption::VALUE_NONE, 'Use original name instead of phpName for form fields')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command allows you to quickly generate Form Type stubs for a given bundle.

  <info>php app/console %command.full_name%</info>

The <info>--force</info> parameter allows you to overwrite existing files.
The <info>--originName</info> parameter allows you to use original name instead of phpName for form fields.
EOT
        )
            ->setName('propel:form:generate');
    }

    /**
     * @see Command
     *
     * @throws \InvalidArgumentException When the target directory does not exist
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_input  = $input;
        $this->_output = $output;
        if ($schemas = $this->getSchemasFromBundle($this->bundle)) {
            foreach ($schemas as $fileName => $array) {
                foreach ($this->getDatabasesFromSchema($array[1]) as $database) {
                    $this->createFormTypeFromDatabase($this->bundle, $database, $input->getArgument('models'), $output, $input->getOption('force'));
                }
            }
        } else {
            $output->writeln(sprintf('No <comment>*schemas.xml</comment> files found in bundle <comment>%s</comment>.', $this->bundle->getName()));
        }
    }

    private function createFormTypeFromDatabase(BundleInterface $bundle, \Database $database, $models)
    {
        $dir = $this->createDirectory($bundle, $this->_output);

        foreach ($database->getTables() as $table) {
            if (0 < count($models) && !in_array($table->getPhpName(), $models)) {
                continue;
            }

            $file = new \SplFileInfo(sprintf('%s/%sType.php', $dir, $table->getPhpName()));

            if (!file_exists($file) || true === $this->_input->getOption('force')) {
                $this->writeFormType($bundle, $table, $file);
            } else {
                $this->_output->writeln(sprintf('File <comment>%-60s</comment> exists, skipped. Try the <info>--force</info> option.', $this->getRelativeFileName($file)));
            }
        }
    }

    private function createDirectory(BundleInterface $bundle)
    {
        $fs = new Filesystem();

        if (!is_dir($dir = $bundle->getPath() . self::DEFAULT_FORM_TYPE_DIRECTORY)) {
            $fs->mkdir($dir);
            $this->writeNewDirectory($this->_output, $dir);
        }

        return $dir;
    }

    private function writeFormType(BundleInterface $bundle, \Table $table, \SplFileInfo $file)
    {
        $modelName       = $table->getPhpName();
        $formTypeContent = file_get_contents(__DIR__ . '/../Resources/skeleton/FormType.php');

        $formTypeContent = str_replace('##NAMESPACE##', $bundle->getNamespace() . str_replace('/', '\\', self::DEFAULT_FORM_TYPE_DIRECTORY), $formTypeContent);
        $formTypeContent = str_replace('##CLASS##', $modelName . 'Type', $formTypeContent);
        $formTypeContent = str_replace('##FQCN##', sprintf('%s\%s', $table->getNamespace(), $modelName), $formTypeContent);
        $formTypeContent = str_replace('##TYPE_NAME##', strtolower($modelName), $formTypeContent);
        $formTypeContent = $this->addFields($table, $formTypeContent);

        file_put_contents($file->getPathName(), $formTypeContent);
        $this->writeNewFile($this->_output, $this->getRelativeFileName($file) . ($this->_input->getOption('force') ? ' (forced)' : ''));
    }

    private function addFields(\Table $table, $formTypeContent)
    {
        $useOriginName = $this->_input->getOption('originName');
        $buildCode = '';
        foreach ($table->getColumns() as $column) {
            if (!$column->isPrimaryKey()) {
                $buildCode .= sprintf("\n        \$builder->add('%s');", $useOriginName ? $column->getName(): lcfirst($column->getPhpName()) );
            }
        }

        return str_replace('##BUILD_CODE##', $buildCode, $formTypeContent);
    }
}
