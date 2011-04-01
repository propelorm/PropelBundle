<?php

namespace Propel\PropelBundle\Command;

use Propel\PropelBundle\Command\PhingCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Util\Filesystem;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * DataSqlCommand.
 *
 * @author William DURAND <william.durand1@gmail.com>  
 */
class DataSqlCommand extends PhingCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDescription('Generates sql from data xml')
            ->addOption('connection', null, InputOption::VALUE_OPTIONAL, 'Set this parameter to define a connection to use')
            ->setHelp(<<<EOT
The <info>propel:data-sql</info> generates sql from data xml.
          
  <info>php app/console propel:data-sql</info>

The <info>--connection</info> parameter allows you to change the connection to use.
The default connection is the active connection (propel.dbal.default_connection).
EOT
            )
            ->setName('propel:data-sql')
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

        $output->writeln(sprintf('<info>Dump data into XML from connection named <comment>%s</comment></info>', $name));
        $dest = $this->getApplication()->getKernel()->getRootDir() . '/propel/sql/';

        $this->callPhing('datasql', array(
            'propel.database.url'       => $defaultConfig['connection']['dsn'],
            'propel.database.database'  => $defaultConfig['adapter'],
            'propel.database.user'      => $defaultConfig['connection']['user'],
            'propel.database.password'  => $defaultConfig['connection']['password'],
            'propel.sql.dir'            => $dest,
            'propel.schema.dir'         => $this->getApplication()->getKernel()->getRootDir() . '/propel/schema/',
        ));

        $output->writeln(sprintf('SQL from XML data dump file is in "<info>%s</info>".', $dest));
    }}
