<?php

namespace Propel\PropelBundle\Command;

use Propel\PropelBundle\Command\PhingCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Util\Filesystem;

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
        list($name, $defaultConfig) = $this->getConnection($input, $output);

        $this->callPhing('reverse', array(
            'propel.project'            => $name,
            'propel.database.url'       => $defaultConfig['connection']['dsn'],
            'propel.database.database'  => $defaultConfig['adapter'],
            'propel.database.user'      => $defaultConfig['connection']['user'],
            'propel.database.password'  => $defaultConfig['connection']['password'],
        ));

        $filesystem = new Filesystem();
        $dest = $this->getApplication()->getKernel()->getRootDir() . '/propel/' . $name . '_reversed_schema.xml';
        $filesystem->copy($this->getTmpDir().'/schema.xml', $dest);

        $output->writeln(sprintf('New generated schema is <comment>%s</comment>.', $dest));
    }
}
