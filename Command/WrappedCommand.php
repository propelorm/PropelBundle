<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
abstract class WrappedCommand extends AbstractCommand
{
    /**
     * Creates the instance of the Propel sub-command to execute.
     *
     * @return \Symfony\Component\Console\Command\Command
     */
    abstract protected function createSubCommandInstance();

    /**
     * Returns all the arguments and options needed by the Propel sub-command.
     *
     * @return array
     */
    abstract protected function getSubCommandArguments(InputInterface $input);

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->addOption('platform',  null, InputOption::VALUE_OPTIONAL, 'The platform')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $params = $this->getSubCommandArguments($input);
        $command = $this->createSubCommandInstance();

        $this->setupBuildTimeFiles();

        return $this->runCommand($command, $params, $input, $output);
    }
}
