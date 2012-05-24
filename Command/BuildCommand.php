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
use Propel\PropelBundle\Command\ModelBuildCommand;
use Propel\PropelBundle\Command\SqlBuildCommand;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;

/**
 * BuildCommand.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author William DURAND <william.durand1@gmail.com>
 */
class BuildCommand extends AbstractCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDescription('Hub for Propel build commands (Model classes, SQL)')
            ->setDefinition(array(
                new InputOption('classes', '', InputOption::VALUE_NONE, 'Build only classes'),
                new InputOption('sql', '', InputOption::VALUE_NONE, 'Build only SQL'),
                new InputOption('insert-sql', '', InputOption::VALUE_NONE, 'Build all and insert SQL'),
                new InputOption('connection', null, InputOption::VALUE_OPTIONAL, 'Set this parameter to define a connection to use')
            ))
            ->setName('propel:build');
    }

    /**
     * @see Command
     *
     * @throws \InvalidArgumentException When the target directory does not exist
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('sql')) {
            $modelCommand = new ModelBuildCommand();
            $modelCommand->setApplication($this->getApplication());
            $modelCommand->execute($input, $output);
        }

        if (!$input->getOption('classes')) {
            $sqlCommand = new SqlBuildCommand();
            $sqlCommand->setApplication($this->getApplication());
            $sqlCommand->execute($input, $output);
        }

        if ($input->getOption('insert-sql')) {
            $insertCommand = new SqlInsertCommand();
            $insertCommand->setApplication($this->getApplication());

            // By-pass the '--force' required option
            $this->addOption('force', '', InputOption::VALUE_NONE, '');
            $input->setOption('force', true);

            $insertCommand->execute($input, $output);
        }
    }
}
