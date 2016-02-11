<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;

use Propel\Generator\Command\DatabaseReverseCommand as BaseDatabaseReverseCommand;

/**
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
class DatabaseReverseCommand extends WrappedCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('propel:database:reverse')
            ->setDescription('Reverse-engineer a XML schema file based on given database')

            ->addArgument('connection',     InputArgument::REQUIRED,           'Connection to use. Example: "default"')
            ->addOption('output-dir',       null, InputOption::VALUE_REQUIRED, 'The output directory', BaseDatabaseReverseCommand::DEFAULT_OUTPUT_DIRECTORY)
            ->addOption('database-name',    null, InputOption::VALUE_REQUIRED, 'The database name to reverse', BaseDatabaseReverseCommand::DEFAULT_DATABASE_NAME)
            ->addOption('schema-name',      null, InputOption::VALUE_REQUIRED, 'The schema name to generate', BaseDatabaseReverseCommand::DEFAULT_SCHEMA_NAME)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function createSubCommandInstance()
    {
        return new BaseDatabaseReverseCommand();
    }

    /**
     * {@inheritdoc}
     */
    protected function getSubCommandArguments(InputInterface $input)
    {
        return array(
            '--output-dir'      => $input->getOption('output-dir'),
            '--database-name'   => $input->getOption('database-name'),
            '--schema-name'     => $input->getOption('schema-name'),
            // this one is an argument, so no leading '--'
            'connection'        => $this->getDsn($input->getArgument('connection')),
        );
    }
}
