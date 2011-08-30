<?php

namespace Propel\PropelBundle\Command;

use Propel\PropelBundle\Command\PhingCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\HttpKernel\Util\Filesystem;

/**
 * BuildCommand.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author William DURAND <william.durand1@gmail.com>
 */
class BuildSqlCommand extends PhingCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDescription('Build the SQL generation code for all tables based on Propel XML schemas')
            ->setHelp(<<<EOT
The <info>propel:build-sql</info> command builds the SQL table generation code based on the XML schemas defined in all Bundles.

  <info>php app/console propel:build-sql</info>
EOT
            )
            ->setName('propel:build-sql')
        ;
    }

    /**
     * @see Command
     *
     * @throws \InvalidArgumentException When the target directory does not exist
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->writeSection($output, '[Propel] You are running the command: propel:build-sql');

        if ($input->getOption('verbose')) {
            $this->additionalPhingArgs[] = 'verbose';
        }

        if (true === $this->callPhing('sql', array('propel.packageObjectModel' => false))) {
            $filesystem = new Filesystem();
            $basePath   = $this->getApplication()->getKernel()->getRootDir(). DIRECTORY_SEPARATOR . 'propel'. DIRECTORY_SEPARATOR . 'sql';
            $sqlMap     = file_get_contents($basePath . DIRECTORY_SEPARATOR . 'sqldb.map');

            foreach ($this->tempSchemas as $schemaFile => $schemaDetails) {
                if (!file_exists($schemaFile)) {
                    continue;
                }

                $sqlFile = str_replace('.xml', '.sql', $schemaFile);
                $targetSqlFile = $schemaDetails['bundle'] . '-' . str_replace('.xml', '.sql', $schemaDetails['basename']);
                $targetSqlFilePath = $basePath . DIRECTORY_SEPARATOR . $targetSqlFile;
                $sqlMap = str_replace($sqlFile, $targetSqlFile, $sqlMap);

                $filesystem->remove($targetSqlFilePath);
                $filesystem->rename($basePath . DIRECTORY_SEPARATOR . $sqlFile, $targetSqlFilePath);

                $output->writeln(sprintf(
                    'Wrote SQL file for bundle <info>%s</info> in <comment>%s</comment>.',
                    $schemaDetails['bundle'],
                    $targetSqlFilePath)
                );
            }

            file_put_contents($basePath . DIRECTORY_SEPARATOR . 'sqldb.map', $sqlMap);
        } else {
            $this->writeSection($output, array(
                '[Propel] Error',
                '',
                'An error has occured during the "sql" task process. To get more details, run the command with the "--verbose" option.'
            ), 'fg=white;bg=red');
        }
    }
}
