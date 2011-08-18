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
 * LoadFixturesCommand
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
            ->setDescription('Load XML fixtures')
            ->addOption('dir', 'd', InputOption::VALUE_OPTIONAL, 'The directory where XML or/and SQL fixtures files are located', $this->defaultFixturesDir)
            ->addOption('xml', '', InputOption::VALUE_NONE, 'Load XML fixtures')
            ->addOption('sql', '', InputOption::VALUE_NONE, 'Load SQL fixtures')
            ->addOption('connection', null, InputOption::VALUE_OPTIONAL, 'Set this parameter to define a connection to use')
            ->setHelp(<<<EOT
The <info>propel:load-fixtures</info> loads <info>XML</info> and/or <info>SQL</info> fixtures.

  <info>php app/console propel:load-fixtures</info>

The <info>--connection</info> parameter allows you to change the connection to use.
The default connection is the active connection (propel.dbal.default_connection).

The <info>--dir</info> parameter allows you to change the directory that contains <info>XML</info> or/and <info>SQL</info> fixtures files <comment>(default: app/propel/fixtures)</comment>.

The <info>--xml</info> parameter allows you to load only <info>XML</info> fixtures.
The <info>--sql</info> parameter allows you to load only <info>SQL</info> fixtures.

You can mix <info>--xml</info> and <info>--sql</info> parameters to load both XML and SQL fixtures.
If none of this parameter is set, all XML and SQL files in the directory will be load.

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
        $this->writeSection($output, '[Propel] You are running the command: propel:load-fixtures');

        $this->absoluteFixturesPath = $this->getApplication()->getKernel()->getRootDir() . DIRECTORY_SEPARATOR . $input->getOption('dir');
        $this->filesystem = new Filesystem();

        $noOptions = (!$input->getOption('xml') && !$input->getOption('sql'));

        if ($input->getOption('xml') || $noOptions) {
            if (0 !== $this->loadXmlFixtures($input, $output)) {
                $output->writeln('<error> >> No XML fixtures found.</error>');
            }
        }

        if ($input->getOption('sql') || $noOptions) {
            if (0 !== $this->loadSqlFixtures($input, $output)) {
                $output->writeln('<error> >> No SQL fixtures found.</error>');
            }
        }
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
        $finder = new Finder();
        $datas  = $finder->name('*.xml')->in($this->absoluteFixturesPath);

        list($name, $defaultConfig) = $this->getConnection($input, $output);

        // Create a "datadb.map" file
        $datadbContent = '';
        foreach($datas as $data) {
            $output->writeln(sprintf('Loaded fixtures from <comment>%s</comment>.', $data));
            $datadbContent .= $data->getFilename() . '=' . $name . PHP_EOL;
        }

        if ('' === $datadbContent) {
            return -1;
        }

        $output->writeln('<info>Loading XML Fixtures.</info>');

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

        return 0;
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
        $finder = new Finder();
        $datas  = $finder->name('*.sql')->in($this->absoluteFixturesPath);

        list($name, $defaultConfig) = $this->getConnection($input, $output);

        // Create a "sqldb.map" file
        $sqldbContent = '';
        foreach($datas as $data) {
            $output->writeln(sprintf('Loaded fixtures from <comment>%s</comment>.', $data));
            $sqldbContent .= $data->getFilename() . '=' . $name . PHP_EOL;
        }

        if ('' === $sqldbContent) {
            return -1;
        }

        $output->writeln('<info>Loading SQL Fixtures.</info>');

        $sqldbFile = $this->absoluteFixturesPath . DIRECTORY_SEPARATOR . 'sqldb.map';
        file_put_contents($sqldbFile, $sqldbContent);

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

        return 0;
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
