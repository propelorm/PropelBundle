<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Command;

use Propel\PropelBundle\Command\AbstractPropelCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * BuildCommand.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author William DURAND <william.durand1@gmail.com>
 */
class BuildModelCommand extends AbstractPropelCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDescription('Build the Propel Object Model classes based on XML schemas')
            ->setHelp(<<<EOT
The <info>propel:build-model</info> command builds the Propel runtime model classes (ActiveRecord, Query, Peer, and TableMap classes) based on the XML schemas defined in all Bundles.

  <info>php app/console propel:build-model</info>
EOT
            )
            ->setName('propel:build-model')
        ;
    }

    /**
     * @see Command
     *
     * @throws \InvalidArgumentException When the target directory does not exist
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->writeSection($output, '[Propel] You are running the command: propel:build-model');

        if ($input->getOption('verbose')) {
           $this->additionalPhingArgs[] = 'verbose';
        }

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
