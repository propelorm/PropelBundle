<?php

namespace Propel\PropelBundle\Command;

use Propel\PropelBundle\Command\PhingCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Util\Filesystem;

/**
 * LoadFixturesCommand : loads XML fixtures files.
 *
 * @author William DURAND <william.durand1@gmail.com>
 */
class LoadFixturesCommand extends PhingCommand
{
    /**
     * Default fixtures directory.
     */
    private $defaultFixturesDir = 'propel/fixtures';

    /**
     * Absolute path for fixtures directory
     */
    private $absoluteFixturesPath = '';

    /**
     * Filesystem for manipulating files
     */
    private $filesystem = null;

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDescription('Load fixtures')
            ->addOption('dir', 'd', InputOption::VALUE_OPTIONAL, 'The directory where XML or/and SQL fixtures files are located', $this->defaultFixturesDir)
            ->addOption('xml', '', InputOption::VALUE_NONE, 'Load xml fixtures')
            ->addOption('sql', '', InputOption::VALUE_NONE, 'Load sql fixtures')
            ->addOption('connection', null, InputOption::VALUE_OPTIONAL, 'Set this parameter to define a connection to use')
            ->setHelp(<<<EOT
The <info>propel:load-fixtures</info> loads <info>XML</info> and/or <info>SQL</info> fixtures.

  <info>php app/console propel:load-fixtures</info>

The <info>--connection</info> parameter allows you to change the connection to use.
The default connection is the active connection (propel.dbal.default_connection).

The <info>--dir</info> parameter allows you to change the directory that contains <info>XML</info> or/and <info>SQL</info> fixtures files <comment>(default: app/propel/fixtures)</comment>.

The <info>--xml</info> parameter allows you to load only <info>XML</info> fixtures.
The <info>--sql</info> parameter allows you to load only <info>SQL</info> fixtures.

You can mix <info>--xml</info> parameter and <info>--sql</info> parameter to load XML and SQL fixtures.
If none of this parameter are set all files, XML and SQL, in the directory will be load.

XML fixtures files are the same XML files you can get with the command <info>propel:data-dump</info>:
<comment>
    <?xml version="1.0" encoding="utf-8"?>
    <dataset name="all">
        <Object Id="..." />
    </dataset>
</comment>
EOT
            )
            ->setName('propel:load-fixtures')
        ;
    }

    /**
     * @see Command
     *
     * @throws \InvalidArgumentException When the target directory does not exist
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->absoluteFixturesPath = $this->getApplication()->getKernel()->getRootDir() . DIRECTORY_SEPARATOR . $input->getOption('dir');
        $this->filesystem = new Filesystem();

        $noOptions = (!$input->getOption('xml') && !$input->getOption('sql'));

        if ($input->getOption('xml') || $noOptions)
        {
            $this->loadXmlFixtures($input, $output);
        }

        if ($input->getOption('sql') || $noOptions)
        {
            $this->loadSqlFixtures($input, $output);
        }

        $output->writeln('<info>Fixtures successfully loaded.</info>');
    }

    /**
     * Load XML fixtures
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return void
     */
    protected function loadXmlFixtures(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Loading XML Fixtures.</info>');

        $finder = new Finder();

        // Create a "datadb.map" file
        $datadbContent = '';
        $datas = $finder->name('*.xml')->in($this->absoluteFixturesPath);
        foreach($datas as $data) {
            $output->writeln(sprintf('Loaded fixtures from <comment>%s</comment>.', $data));

            $datadbContent .= $data->getFilename() . '=default' . PHP_EOL;
        }

        $datadbFile = $this->absoluteFixturesPath . '/datadb.map';
        file_put_contents($datadbFile, $datadbContent);

        $dest = $this->getApplication()->getKernel()->getRootDir() . '/propel/sql/';
        $this->callPhing('datasql', array(
             'propel.sql.dir'            => $dest,
             'propel.schema.dir'         => $this->absoluteFixturesPath,
        ));

        // Insert SQL
        $insertCommand = new InsertSqlCommand();
        $insertCommand->setApplication($this->getApplication());

        // By-pass the '--force' required option for inserting SQL
        $this->addOption('force', '', InputOption::VALUE_NONE, '');
        $input->setOption('force', true);

        $insertCommand->execute($input, $output);

        $this->removeTemporaryFiles();

        $this->filesystem->remove($datadbFile);
    }

    /**
     * Load SQL fixtures
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return void
     */
    protected function loadSqlFixtures(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Loading SQL Fixtures.</info>');

        $finder = new Finder();

        // Create a "sqldb.map" file
        $sqldbContent = '';
        $datas = $finder->name('*.sql')->in($this->absoluteFixturesPath);
        foreach($datas as $data) {
            $output->writeln(sprintf('Loaded fixtures from <comment>%s</comment>.', $data));

            $sqldbContent .= $data->getFilename() . '=default' . PHP_EOL;
        }

        $sqldbFile = $this->absoluteFixturesPath . DIRECTORY_SEPARATOR . 'sqldb.map';

        file_put_contents($sqldbFile, $sqldbContent);

        list($name, $defaultConfig) = $this->getConnection($input, $output);

        $this->callPhing('insert-sql', array(
            'propel.database.url'       => $defaultConfig['connection']['dsn'],
            'propel.database.database'  => $defaultConfig['adapter'],
            'propel.database.user'      => $defaultConfig['connection']['user'],
            'propel.database.password'  => $defaultConfig['connection']['password'],
            'propel.sql.dir'            => $this->absoluteFixturesPath,
            'propel.schema.dir'         => $this->absoluteFixturesPath,
        ));

        $this->removeTemporaryFiles();

        $this->filesystem->remove($this->absoluteFixturesPath . DIRECTORY_SEPARATOR . 'sqldb.map');
    }

    /**
     * Remove all temporary files
     *
     * @return void
     */
    protected function removeTemporaryFiles()
    {
        $finder = new Finder();

        // Delete temporary files
        $datas = $finder->name('*_schema.xml')->name('build*')->in($this->absoluteFixturesPath);
        foreach($datas as $data) {
          $this->filesystem->remove($data);
        }
    }
}
