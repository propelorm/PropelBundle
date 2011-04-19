<?php

namespace Propel\PropelBundle\Command;

use Propel\PropelBundle\Command\PhingCommand;
use Symfony\Component\Console\Input\InputOption;
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
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDescription('Load XML fixtures')
            ->addOption('dir', 'd', InputOption::VALUE_REQUIRED, 'The directory where XML fixtures files are located')
            ->addOption('connection', null, InputOption::VALUE_OPTIONAL, 'Set this parameter to define a connection to use')
            ->setHelp(<<<EOT
The <info>propel:load-fixtures</info> loads XML fixtures.

  <info>php app/console propel:load-fixtures</info>

The <info>--connection</info> parameter allows you to change the connection to use.
The default connection is the active connection (propel.dbal.default_connection).

The <info>--dir</info> parameter allows you to change the directory that contains XML fixtures files (default: <info>app/propel/fixtures</info>).

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
        $finder = new Finder();
        $filesystem = new Filesystem();
        $dir = $input->getOption('dir') ?: $this->defaultFixturesDir;
        $fixturesDir = $this->getApplication()->getKernel()->getRootDir() . '/' . $dir;

        // Create a "datadb.map" file
        $datadbContent = '';
        $datas = $finder->name('*.xml')->in($fixturesDir);
        foreach($datas as $data) {
            $output->writeln(sprintf('Loaded fixtures from <comment>%s</comment>.', $data));
            $datadbContent .= $data->getFilename() . '=default' . "\n";
        }

        $datadbFile = $fixturesDir . '/datadb.map';
        file_put_contents($datadbFile, $datadbContent);

        $dest = $this->getApplication()->getKernel()->getRootDir() . '/propel/sql/';
        $this->callPhing('datasql', array(
            'propel.sql.dir'            => $dest,
            'propel.schema.dir'         => $fixturesDir,
        ));

        // Insert SQL
        $insertCommand = new InsertSqlCommand();
        $insertCommand->setApplication($this->getApplication());

        // By-pass the '--force' required option
        $this->addOption('--force', '', InputOption::VALUE_NONE, '');
        $input->setOption('force', true);

        $insertCommand->execute($input, $output);

        // Delete temporary files
        $finder = new Finder();
        $datas = $finder->name('*_schema.xml')->name('build*')->in($fixturesDir);
        foreach($datas as $data) {
            $filesystem->remove($data);
        }

        $filesystem->remove($datadbFile);

        $output->writeln('<info>Fixtures successfully loaded.</info>');
    }
}
