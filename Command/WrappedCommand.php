<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Command;

use Propel\Generator\Command\AbstractCommand as BaseCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

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
            ->addOption('platform',  null, InputOption::VALUE_REQUIRED,  'The platform', BaseCommand::DEFAULT_PLATFORM)
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

    protected function runCommand(Command $command, array $parameters, InputInterface $input, OutputInterface $output)
    {
        // add the command's name to the parameters
        array_unshift($parameters, $this->getName());

        // merge the default parameters
        $parameters = array_merge(array(
            '--input-dir'   => $this->cacheDir,
            '--verbose'     => $input->getOption('verbose'),
        ), $parameters);

        if ($input->hasOption('platform')) {
            $parameters['--platform'] = $input->getOption('platform');
        }

        $command->setApplication($this->getApplication());

        // and run the sub-command
        return $command->run(new ArrayInput($parameters), $output);
    }
}
