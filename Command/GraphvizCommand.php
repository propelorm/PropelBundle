<?php

namespace Propel\PropelBundle\Command;

use Propel\PropelBundle\Command\PhingCommand;
use Symfony\Component\Console\Input\InputOption;
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
 * GraphvizCommand.
 *
 * @author William DURAND <william.durand1@gmail.com>  
 */
class GraphvizCommand extends PhingCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDescription('Generates Graphviz file for your project')
            ->setHelp(<<<EOT
The <info>propel:graphviz</info> generates Graphviz file for your project.
          
  <info>php app/console propel:graphviz</info>
EOT
            )
            ->setName('propel:graphviz')
        ;
    }

    /**
     * @see Command
     *
     * @throws \InvalidArgumentException When the target directory does not exist
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {  
        $dest = $this->getApplication()->getKernel()->getRootDir() . '/propel/graph/';

        $this->callPhing('graphviz', array(
            'propel.graph.dir'    => $dest,
        ));

        $output->writeln(sprintf('Graphviz file is in "<info>%s</info>".', $dest));
    }
}
