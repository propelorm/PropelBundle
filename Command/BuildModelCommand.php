<?php

namespace Propel\PropelBundle\Command;

use Propel\PropelBundle\Command\PhingCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * BuildCommand.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author William DURAND <william.durand1@gmail.com>
 */
class BuildModelCommand extends PhingCommand
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
        if ($input->getOption('verbose')) {
           $this->additionalPhingArgs[]= 'verbose';
        }
        $this->callPhing('om');

        foreach ($this->tempSchemas as $schemaFile => $schemaDetails) {
            $output->writeln(sprintf('Built Model classes for bundle <info>%s</info> from <comment>%s</comment>.', $schemaDetails['bundle'], $schemaDetails['path']));
        }
    }
}
