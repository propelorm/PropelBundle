<?php

namespace Propel\PropelBundle\Command;

use Propel\PropelBundle\Command\PhingCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * MigrationGenerateDiffCommand.
 *
 * @author William DURAND <william.durand1@gmail.com>  
 */
class MigrationGenerateDiffCommand extends PhingCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDescription('Generates SQL diff between the XML schemas and the current database structure')
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
        $this->callPhing('diff');

        foreach (explode("\n", $this->buffer) as $line) {
            if (false !== strpos($line, '[propel-sql-diff]')) {
                $arr  = preg_split('#\[propel-sql-diff\] #', $line);
                $info = $arr[1];

                if ('"' === $info[0]) {
                    $info = sprintf('<info>%s</info>', $info);
                }

                $output->writeln($info);
            }
        }
    }
}
