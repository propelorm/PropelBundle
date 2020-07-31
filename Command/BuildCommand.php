<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
class BuildCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('propel:build')
            ->setDescription('Hub for Propel build commands (Model classes, SQL)')

            ->setDefinition(array(
                new InputOption('classes', '', InputOption::VALUE_NONE, 'Build only classes'),
                new InputOption('sql', '', InputOption::VALUE_NONE, 'Build only SQL'),
                new InputOption('insert-sql', '', InputOption::VALUE_NONE, 'Build all and insert SQL'),
                new InputOption('connection', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Connection to use. Example: default, bookstore')
            ))
        ;
    }

    /**
     * @see Command
     *
     * @throws \InvalidArgumentException When the target directory does not exist
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('sql')) {
            $in = new ArrayInput(array(
                'command'       => 'propel:model:build',
                '--connection'  => $input->getOption('connection'),
                '--verbose'     => $input->getOption('verbose')
            ));
            $cmd = $this->getApplication()->find('propel:model:build');
            $cmd->run($in, $output);
        }

        if (!$input->getOption('classes')) {
            $in = new ArrayInput(array(
                'command'       => 'propel:build:sql',
                '--connection'  => $input->getOption('connection'),
                '--verbose'     => $input->getOption('verbose'),
            ));
            $cmd = $this->getApplication()->find('propel:sql:build');
            $cmd->run($in, $output);
        }

        if ($input->getOption('insert-sql')) {
            $in = new ArrayInput(array(
                'command'       => 'propel:sql:insert',
                '--connection'  => $input->getOption('connection'),
                '--force'       => true,
                '--verbose'     => $input->getOption('verbose'),
            ));
            $cmd = $this->getApplication()->find('propel:sql:insert');
            $cmd->run($in, $output);
        }
    }
}
