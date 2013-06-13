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
            ->addOption('connection', null, InputOption::VALUE_OPTIONAL, 'Set this parameter to define a connection to use')
            ->setHelp(<<<EOT
The <info>propel:migration:generate-diff</info> command compares the current database structure and the available schemas. If there is a difference, it creates a migration file.

  <info>php app/console propel:migration:generate-diff</info>
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
        if (true === $this->callPhing('diff')) {
            $this->writeSummary($output, 'propel-sql-diff');
        } elseif ( strpos( $this->buffer, 'Uncommitted migrations have been found' ) ) {
            $this->writeSection($output, array(
                '[Propel] Error',
                '',
                'Uncommitted migrations have been found. You should either execute or delete them before rerunning the propel:migration:generate-diff command.'
            ), 'fg=white;bg=red');
        } else {
            $this->writeTaskError($output, 'propel-sql-diff');
        }
    }
}
