<?php

namespace Propel\PropelBundle\Command;

use Propel\PropelBundle\Command\PhingCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/**
 * DatabaseDropCommand class.
 * Useful to drop a database.
 *
 * @author William DURAND
 */
class TableDropCommand extends PhingCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDescription('Drop a given table or all tables in the database.')
            ->addArgument('table', InputArgument::IS_ARRAY, 'Set this parameter to défine which table to delete (default all the table in the database.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Set this parameter to execute this action.')
            ->addOption('connection', null, InputOption::VALUE_OPTIONAL, 'Set this parameter to define a connection to use')
            ->setHelp(<<<EOT
The <info>propel:table:drop</info> command will drop one or several table.

  <info>php app/console propel:table:drop</info>

The <info>table</info> arguments define the list of table which has to be delete <comment>(default: all table)</comment>.
The <info>--force</info> parameter has to be used to actually drop the table.
The <info>--connection</info> parameter allows you to change the connection to use.
The default connection is the active connection (propel.dbal.default_connection).
EOT
            )
            ->setName('propel:table:drop');
    }

  /**
   * @see Command
   *
   * @throws \InvalidArgumentException When the target directory does not exist
   */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
      $tablesToDelete = $input->getArgument('table');
      
      if ($input->getOption('force'))
      {
        $nbTable = count($tablesToDelete);
        $tablePlural = (($nbTable > 1 || $nbTable == 0) ? 's' : '' );

        if ('prod' === $this->getApplication()->getKernel()->getEnvironment())
        {
          $this->writeSection($output, 'WARNING: you are about to drop ' . (count($input->getArgument('table')) ?: 'all') . ' table' . $tablePlural . ' in production !', 'bg=red;fg=white');

          if (false === $this->askConfirmation($output, 'Are you sure ? (y/n) ', false))
          {
            $output->writeln('Aborted, nice decision !');
            return -2;
          }
        }

        $this->writeSection($output, '[Propel] You are running the command: propel:table:drop');

        try
        {
          list($name, $config) = $this->getConnection($input, $output);
          $connection = \Propel::getConnection($name);

          $showStatement = $connection->prepare('SHOW TABLES;');
          $showStatement->execute();
          $allTables = $showStatement->fetchAll(\PDO::FETCH_COLUMN);

          if ($nbTable)
          {
            foreach ($tablesToDelete as $tableToDelete)
            {
              if(!array_search($tableToDelete, $allTables))
              {
                throw new \InvalidArgumentException(sprintf('Table %s doesn\'t exist in the database.', $tableToDelete));
              }
            }
          }
          else
          {
            $tablesToDelete = $allTables;
          }

          $tablesToDelete = join(', ', $tablesToDelete);

          if ($tablesToDelete != '')
          {
            $dropStatement = $connection->prepare('DROP TABLE ' . $tablesToDelete . ' ;');
            $dropStatement->execute();

            $output->writeln(sprintf('Table' . $tablePlural . ' <info><comment>%s</comment> has been dropped.</info>', $tablesToDelete));
          }
          else
          {
            $output->writeln('No table <info>has been dropped</info>');
          }
        }
        catch (\Exception $e)
        {
          $output->writeln(sprintf('<error>An error has occured: %s</error>', $e->getMessage()));
        }
      }
      else
      {
        $output->writeln('<error>You have to use --force to drop some table.</error>');
      }
    }
}
