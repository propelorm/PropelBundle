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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * ModelBuildCommand.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author William DURAND <william.durand1@gmail.com>
 */
class ModelBuildCommand extends AbstractCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDescription('Build the Propel Object Model classes based on XML schemas')
            ->addArgument('bundle', InputArgument::OPTIONAL, 'The bundle to generate model classes from')
            ->addOption('connection', null, InputOption::VALUE_OPTIONAL, 'Set this parameter to define a connection to use')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command builds the Propel runtime model classes (ActiveRecord, Query, Peer, and TableMap classes) based on the XML schemas defined in all Bundles.

  <info>php app/console %command.full_name%</info>
EOT
            )
            ->setName('propel:model:build')
        ;
    }

    /**
     * @see Command
     *
     * @throws \InvalidArgumentException When the target directory does not exist
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (true === $this->callPhing('om')) {
            foreach ($this->tempSchemas as $schemaFile => $schemaDetails) {
                $output->writeln(sprintf(
                    '>>  <info>%20s</info>    Generated model classes from <comment>%s</comment>',
                    $schemaDetails['bundle'],
                    $schemaDetails['basename']
                ));
            }
        } else {
            $this->writeTaskError($output, 'om');
        }
    }
}
