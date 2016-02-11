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

use Propel\Generator\Command\GraphvizGenerateCommand as BaseGraphvizGenerateCommand;

/**
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
class GraphvizGenerateCommand extends WrappedCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('propel:graphviz:generate')
            ->setDescription('Generate Graphviz files (.dot)')

            ->addOption('output-dir',  null, InputOption::VALUE_REQUIRED,  'The output directory', BaseGraphvizGenerateCommand::DEFAULT_OUTPUT_DIRECTORY)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function createSubCommandInstance()
    {
        return new BaseGraphvizGenerateCommand();
    }

    /**
     * {@inheritdoc}
     */
    protected function getSubCommandArguments(InputInterface $input)
    {
        return array(
            '--output-dir' => $input->getOption('output-dir'),
        );
    }
}
