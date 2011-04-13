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
 * MigrationStatusCommand.
 *
 * @author William DURAND <william.durand1@gmail.com>  
 */
class MigrationStatusCommand extends PhingCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDescription('Lists the migrations yet to be executed')
            ->setHelp(<<<EOT
The <info>propel:migration:status</info> command checks the version of the database structure, and looks for migration files not yet executed (i.e. with a greater version timestamp). 
          
  <info>php app/console propel:migration:status</info>
EOT
            )
            ->setName('propel:migration:status')
        ;
    }

    /**
     * @see Command
     *
     * @throws \InvalidArgumentException When the target directory does not exist
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {   
        $this->callPhing('status');

        foreach (explode("\n", $this->buffer) as $line) {
            if (false !== strpos($line, '[propel-migration-status]')) {
                $arr  = preg_split('#\[propel-migration-status\] #', $line);
                $info = $arr[1];

                if (' ' === $info[0]) {
                    $info = sprintf('<info>%s</info>', $info);
                }

                $output->writeln($info);
            }
        }
    }
}
