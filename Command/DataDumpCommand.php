<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Command;

use Propel\PropelBundle\Command\PhingCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Util\Filesystem;

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
        list($name, $defaultConfig) = $this->getConnection($input, $output);

        $ret = $this->callPhing('datadump', array(
            'propel.database.url'       => $defaultConfig['connection']['dsn'],
            'propel.database.database'  => $defaultConfig['adapter'],
            'propel.database.user'      => $defaultConfig['connection']['user'],
            'propel.database.password'  => $defaultConfig['connection']['password'],
            'propel.schema.dir'         => $this->getApplication()->getKernel()->getRootDir() . '/propel/schema/',
        ));

        if ($ret) {
            $finder     = new Finder();
            $filesystem = new Filesystem();

            $datas = $finder->name('*_data.xml')->in($this->getTmpDir());

            foreach($datas as $data) {
                $dest = $this->getApplication()->getKernel()->getRootDir() . self::$destPath . '/xml/' . $data->getFilename();

                $filesystem->copy((string) $data, $dest);
                $filesystem->remove($data);

                $output->writeln(sprintf('<info>Wrote dumped data in <comment>%s</comment></info>.', $dest));
            }

            if (iterator_count($datas) <= 0) {
                $output->writeln('No dumped files.');
            }
        } else {
            $this->writeTaskError('datadump', false);
        }
    }
}
