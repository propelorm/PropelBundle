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
 * ReverseCommand.
 *
 * @author William DURAND <william.durand1@gmail.com>  
 */
class ReverseCommand extends PhingCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDescription('Generate XML schema from reverse-engineered database')
            ->addOption('connection', null, InputOption::VALUE_OPTIONAL, 'Set this parameter to define a connection to use')
            ->setHelp(<<<EOT
The <info>propel:reverse</info> command generates an XML schema from reverse-engineered database.
          
  <info>php app/console propel:reverse</info>

  The <info>--connection</info> parameter allows you to change the connection to use.
  The default connection is the active connection (propel.dbal.default_connection).
EOT
            )
            ->setName('propel:reverse')
        ;
    }

    /**
     * @see Command
     *
     * @throws \InvalidArgumentException When the target directory does not exist
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {  
        $container = $this->getApplication()->getKernel()->getContainer();
        $propelConfiguration = $container->get('propel.configuration');
        $name = $input->getOption('connection') ? $input->getOption('connection') : $container->getParameter('propel.dbal.default_connection');

        if (isset($propelConfiguration['datasources'][$name])) {
            $defaultConfig = $propelConfiguration['datasources'][$name];
        } else {
            throw new \InvalidArgumentException(sprintf('Connection named %s doesn\'t exist', $name));
        }

        $output->writeln(sprintf('<info>Generate XML schema from connection named <comment>%s</comment></info>', $name));

        $this->callPhing('reverse', array(
            'propel.project'            => $name,
            'propel.database.url'       => $defaultConfig['connection']['dsn'],
            'propel.database.database'  => $defaultConfig['adapter'],
            'propel.database.user'      => $defaultConfig['connection']['user'],
            'propel.database.password'  => $defaultConfig['connection']['password'],
        ));
    }
}
