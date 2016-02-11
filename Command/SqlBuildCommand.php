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

/**
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
class SqlBuildCommand extends WrappedCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('propel:sql:build')
            ->setDescription('Build SQL files')
            ->addOption('sql-dir', null, InputOption::VALUE_REQUIRED, 'The SQL files directory')
            ->addOption('overwrite', null, InputOption::VALUE_NONE, '')
            ->addOption('connection', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Connection to use. Example: default, bookstore')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function createSubCommandInstance()
    {
        return new \Propel\Generator\Command\SqlBuildCommand();
    }

    /**
     * {@inheritdoc}
     */
    protected function getSubCommandArguments(InputInterface $input)
    {
        $defaultSqlDir = sprintf('%s/propel/sql', $this->getApplication()->getKernel()->getRootDir());

        return array(
            '--connection'  => $this->getConnections($input->getOption('connection')),
            '--output-dir'  => $input->getOption('sql-dir') ?: $defaultSqlDir,
            '--overwrite' => $input->getOption('overwrite')
        );
    }
}
