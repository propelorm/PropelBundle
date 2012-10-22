<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Command;

use Propel\PropelBundle\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * MigrationMigrateCommand.
 *
 * @author William DURAND <william.durand1@gmail.com>
 */
class MigrationMigrateCommand extends AbstractCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDescription('Executes the next migrations up')
            ->setDefinition(array(
                new InputOption('--up', '', InputOption::VALUE_NONE, 'Executes the next migration up'),
                new InputOption('--down', '', InputOption::VALUE_NONE, 'Executes the next migration down'),
            ))
            ->setHelp(<<<EOT
The <info>propel:migration:migrate</info> command checks the version of the database structure, looks for migrations files not yet executed (i.e. with a greater version timestamp), and executes them.

    <info>php app/console propel:migration:migrate [--up] [--down]</info>

    <info>php app/console propel:migration:migrate</info> : is the default command, it <comment>executes all</comment> migrations files.

    <info>php app/console propel:migration:migrate --up</info> : checks the version of the database structure, looks for migrations files not yet executed (i.e. with a greater version timestamp), and <comment>executes the first one</comment> of them.

    <info>php app/console propel:migration:migrate --down</info> : checks the version of the database structure, and looks for migration files already executed (i.e. with a lower version timestamp). <comment>The last executed migration found is reversed.</comment>
EOT
            )
            ->setName('propel:migration:migrate')
        ;
    }

    /**
     * @see Command
     *
     * @throws \InvalidArgumentException When the target directory does not exist
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('down')) {
            $this->callPhing('migration-down');
        } elseif ($input->getOption('up')) {
            $this->callPhing('migration-up');
        } else {
            $this->callPhing('migrate');
        }

        $this->writeSummary($output, 'propel-migration');
    }
}
