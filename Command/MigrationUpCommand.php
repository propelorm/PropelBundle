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

use Propel\Generator\Command\MigrationUpCommand as BaseMigrationCommand;

/**
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
class MigrationUpCommand extends WrappedCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('propel:migration:up')
            ->setDescription('Execute (only one) next migration')

            ->addOption('connection',       null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Connection to use. Example: default, bookstore')
            ->addOption('migration-table',  null, InputOption::VALUE_OPTIONAL,  'Migration table name (if none given, the configured table is used)', null)
            ->addOption('output-dir',       null, InputOption::VALUE_OPTIONAL,  'The output directory')
            ->addOption('fake',             null, InputOption::VALUE_NONE,       'Does not touch the actual schema, but marks next migration as executed.')
            ->addOption('force',            null, InputOption::VALUE_NONE,       'Continues with the migration even when errors occur.')
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
            '--connection'      => $this->getConnections($input->getOption('connection')),
            '--migration-table' => $input->getOption('migration-table') ?: $this->getMigrationsTable(),
            '--output-dir'      => $input->getOption('output-dir') ?: $defaultOutputDir,
            '--fake'            => $input->getOption('fake'),
            '--force'           => $input->getOption('force'),
        );
    }
}
