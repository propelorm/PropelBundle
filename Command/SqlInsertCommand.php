<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
class SqlInsertCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('propel:sql:insert')
            ->setDescription('Insert SQL statements')
            ->addOption('connection', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Connection to use. Example: default, bookstore')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function createSubCommandInstance()
    {
        return new \Propel\Generator\Command\SqlInsertCommand();
    }

    /**
     * {@inheritdoc}
     */
    protected function getSubCommandArguments(InputInterface $input)
    {
        return array(
            '--connection' => $this->getConnections($input->getOption('connection')),
        );
    }
}
