<?php

namespace Propel\PropelBundle\Command;

use Propel\PropelBundle\Command\PhingCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Bundle\FrameworkBundle\Util\Filesystem;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * DataDumpCommand.
 *
 * @author William DURAND <william.durand1@gmail.com>  
 */
class DataDumpCommand extends PhingCommand
{
    protected static $destPath = '/propel/dump';

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDescription('Dump data from database into xml file')
            ->addOption('connection', null, InputOption::VALUE_OPTIONAL, 'Set this parameter to define a connection to use')
            ->setHelp(<<<EOT
The <info>propel:data-dump</info> dumps data from database into xml file.
          
  <info>php app/console propel:data-dump</info>

  The <info>--connection</info> parameter allows you to change the connection to use.
  The default connection is the active connection (propel.dbal.default_connection).
EOT
            )
            ->setName('propel:data-dump')
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

        $this->callPhing('datadump', array(
            'propel.database.url'       => $defaultConfig['connection']['dsn'],
            'propel.database.database'  => $defaultConfig['adapter'],
            'propel.database.user'      => $defaultConfig['connection']['user'],
            'propel.database.password'  => $defaultConfig['connection']['password'],
        ));

        $finder = new Finder();
        $filesystem = new Filesystem();

        $datas = $finder->name('*_data.xml')->in($this->getTmpDir());

        foreach($datas as $data) {
            $dest = $this->getApplication()->getKernel()->getRootDir() . self::$destPath . '/xml/' . $data->getFilename();

            $filesystem->copy((string) $data, $dest);
            $output->writeln(sprintf('Wrote dumped data in "<info>%s</info>".', $dest));
        }

        if (count($datas) <= 0) {
            $output->writeln('No new dumped files.');
        }
    }
}
