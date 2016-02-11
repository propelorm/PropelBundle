<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;

use Propel\Generator\Command\MigrationDiffCommand as BaseMigrationCommand;

/**
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
class MigrationDiffCommand extends WrappedCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('propel:migration:diff')
            ->setDescription('Generate diff classes')

            ->addOption('connection',       null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Connection to use. Example: default, bookstore')
            ->addOption('output-dir',       null, InputOption::VALUE_OPTIONAL,    'The output directory')
            ->addOption('migration-table',  null, InputOption::VALUE_OPTIONAL,  'Migration table name (if none given, the configured table is used)', null)
            ->addOption('table-renaming',     null, InputOption::VALUE_NONE,      'Detect table renaming', null)
            ->addOption('editor',             null, InputOption::VALUE_OPTIONAL,  'The text editor to use to open diff files', null)
            ->addOption('skip-removed-table', null, InputOption::VALUE_NONE,      'Option to skip removed table from the migration')
            ->addOption('skip-tables',        null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'List of excluded tables', array())
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function createSubCommandInstance()
    {
        return new BaseMigrationCommand();
    }

    /**
     * {@inheritdoc}
     */
    protected function getSubCommandArguments(InputInterface $input)
    {
        $defaultOutputDir = $this->getApplication()->getKernel()->getRootDir().'/propel/migrations';

        return array(
            '--connection'          => $this->getConnections($input->getOption('connection')),
            '--migration-table'     => $input->getOption('migration-table') ?: $this->getMigrationsTable(),
            '--output-dir'          => $input->getOption('output-dir') ?: $defaultOutputDir,
            '--table-renaming'      => $input->getOption('table-renaming'),
            '--editor'              => $input->getOption('editor'),
            '--skip-removed-table'  => $input->getOption('skip-removed-table'),
            '--skip-tables'         => $input->getOption('skip-tables'),
        );
    }
}
