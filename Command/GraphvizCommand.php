<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Command;

use Propel\PropelBundle\Command\PhingCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
        $this->writeSection($output, '[Propel] You are running the command: propel:graphviz');

        $dest = $this->getApplication()->getKernel()->getRootDir() . '/propel/graph/';

        $this->callPhing('graphviz', array(
            'propel.graph.dir'    => $dest,
        ));

        $output->writeln(sprintf('Graphviz file is in <info>%s</info>.', $dest));
    }
}
