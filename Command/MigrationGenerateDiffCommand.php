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
use Symfony\Component\Console\Output\OutputInterface;

/**
 * MigrationGenerateDiffCommand.
 *
 * @author William DURAND <william.durand1@gmail.com>
 */
class MigrationGenerateDiffCommand extends AbstractCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDescription('Generates SQL diff between the XML schemas and the current database structure')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command compares the current database structure and the available schemas. If there is a difference, it creates a migration file.

  <info>php %command.full_name%</info>
EOT
            )
            ->setName('propel:migration:generate-diff')
        ;
    }

    /**
     * @see Command
     *
     * @throws \InvalidArgumentException When the target directory does not exist
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->callPhing('diff');

        $this->writeSummary($output, 'propel-sql-diff');
    }
}
