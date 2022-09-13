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
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
class SqlInsertCommand extends WrappedCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('propel:sql:insert')
            ->setDescription('Insert SQL statements')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Set this parameter to execute this action.')
            ->addOption('sql-dir', null, InputOption::VALUE_REQUIRED, 'The SQL files directory')
            ->addOption('connection', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Connection to use. Example: default, bookstore')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function createSubCommandInstance()
    {
        return new \Propel\Generator\Command\SqlInsertCommand();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('force')) {
            return parent::execute($input, $output);
        } else {
            $output->writeln('<error>You have to use --force to execute all SQL statements.</error>');
            return 1;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getSubCommandArguments(InputInterface $input)
    {
        $defaultSqlDir = $this->getContainer()->getParameter('propel.configuration')['paths']['sqlDir'];

        return array(
            '--connection'  => $this->getConnections($input->getOption('connection')),
            '--sql-dir'     => $input->getOption('sql-dir') ?: $defaultSqlDir,
        );
    }
}
