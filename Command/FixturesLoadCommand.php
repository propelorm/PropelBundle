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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Util\Filesystem;

use Propel\PropelBundle\Command\AbstractPropelCommand;
use Propel\PropelBundle\DataFixtures\Loader\YamlDataLoader;
use Propel\PropelBundle\DataFixtures\Loader\XmlDataLoader;

/**
 * FixturesLoadCommand
 *
 * @author William DURAND <william.durand1@gmail.com>
 */
class FixturesLoadCommand extends AbstractPropelCommand
{
    /**
     * Default fixtures directory.
     * @var string
     */
    private $defaultFixturesDir = 'app/propel/fixtures';
    /**
     * Absolute path for fixtures directory
     * @var string
     */
    private $absoluteFixturesPath = '';
    /**
     * Filesystem for manipulating files
     * @var \Symfony\Component\HttpKernel\Util\Filesystem
     */
    private $filesystem = null;

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDescription('Load XML, SQL and/or YAML fixtures')
            ->addOption(
                'dir', 'd', InputOption::VALUE_OPTIONAL,
                'The directory where XML, SQL and/or YAML fixtures files are located',
                $this->defaultFixturesDir
            )
            ->addOption('xml', '', InputOption::VALUE_NONE, 'Load XML fixtures')
            ->addOption('sql', '', InputOption::VALUE_NONE, 'Load SQL fixtures')
            ->addOption('yml', '', InputOption::VALUE_NONE, 'Load YAML fixtures')
            ->addOption('connection', null, InputOption::VALUE_OPTIONAL, 'Set this parameter to define a connection to use')
            ->setHelp(<<<EOT
The <info>propel:fixtures:load</info> loads <info>XML</info>, <info>SQL</info> and/or <info>YAML</info> fixtures.

  <info>php app/console propel:fixtures:load</info>

The <info>--connection</info> parameter allows you to change the connection to use.
The default connection is the active connection (propel.dbal.default_connection).

The <info>--dir</info> parameter allows you to change the directory that contains <info>XML</info> or/and <info>SQL</info> fixtures files <comment>(default: app/propel/fixtures)</comment>.

The <info>--xml</info> parameter allows you to load only <info>XML</info> fixtures.
The <info>--sql</info> parameter allows you to load only <info>SQL</info> fixtures.
The <info>--yml</info> parameter allows you to load only <info>YAML</info> fixtures.

You can mix <info>--xml</info>, <info>--sql</info> and <info>--yml</info> parameters to load XML, YAML and SQL fixtures at the same time.
If none of this parameter is set, all XML, YAML and SQL files in the directory will be load.

XML fixtures files are the same XML files you can get with the command <info>propel:data-dump</info>:
<comment>
    <Fixtures>
        <Object Namespace="Awesome">
            <o1 Title="My title" MyFoo="bar" />
        </Object>
        <Related Namespace="Awesome">
            <r1 ObjectId="o1" Description="Hello world !" />
        </Related>
    </Fixtures>
</comment>

YAML fixtures are:
<comment>
    \Awesome\Object:
        o1:
            Title: My title
            MyFoo: bar

    \Awesome\Related:
        r1:
            ObjectId: o1
            Description: Hello world !
</comment>
EOT
            )
            ->setName('propel:fixtures:load')
        ;
    }

    /**
     * @see Command
     *
     * @throws \InvalidArgumentException When the target directory does not exist
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->writeSection($output, '[Propel] You are running the command: propel:fixtures:load');

        $this->filesystem = new Filesystem();
        $this->absoluteFixturesPath = realpath($this->getApplication()->getKernel()->getRootDir() . '/../' . $input->getOption('dir'));

        if ($input->getOption('verbose')) {
           $this->additionalPhingArgs[] = 'verbose';
        }

        if (!$this->absoluteFixturesPath && !file_exists($this->absoluteFixturesPath)) {
            return $this->writeSection($output, array(
                'The fixtures directory does not exist.'
            ), 'fg=white;bg=red');
        }

        $noOptions = (!$input->getOption('xml') && !$input->getOption('sql') && !$input->getOption('yml'));

        if ($input->getOption('sql') || $noOptions) {
            if (-1 === $this->loadSqlFixtures($input, $output)) {
                $output->writeln('<info>No SQL fixtures found.</info>');
            }
        }

        if ($input->getOption('xml') || $noOptions) {
            if (-1 === $this->loadFixtures($input, $output, 'xml')) {
                $output->writeln('<info>No XML fixtures found.</info>');
            }
        }

        if ($input->getOption('yml') || $noOptions) {
            if (-1 === $this->loadFixtures($input, $output, 'yml')) {
                $output->writeln('<info>No YAML fixtures found.</info>');
            }
        }
    }

    /**
     * Load fixtures
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return void
     */
    protected function loadFixtures(InputInterface $input, OutputInterface $output, $type = null)
    {
        if (null === $type) {
            return;
        }

        $finder = new Finder();
        $tmpdir = $this->getApplication()->getKernel()->getRootDir() . '/cache/propel';
        $datas  = $finder->name('*.' . $type)->in($this->absoluteFixturesPath);

        if (count(iterator_to_array($datas)) === 0) {
            return -1;
        }

        list($name, $defaultConfig) = $this->getConnection($input, $output);

        if ('yml' === $type) {
            $loader = new YamlDataLoader($this->getApplication()->getKernel()->getRootDir());
        } elseif ('xml' === $type) {
            $loader = new XmlDataLoader($this->getApplication()->getKernel()->getRootDir());
        } else {
            return;
        }

        try {
            $nb = $loader->load($datas, $name);
        } catch (\Exception $e) {
            $this->writeSection($output, array(
                '[Propel] Exception',
                '',
                $e->getMessage()), 'fg=white;bg=red');

            return false;
        }

        $this->writeSection(
            $output,
            sprintf('<comment>%s</comment> %s fixtures file%s loaded.', $nb, strtoupper($type), $nb > 1 ? 's' : ''),
            'fg=white;bg=black'
        );

        return true;
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
        $tmpdir = $this->getApplication()->getKernel()->getRootDir() . '/cache/propel';
        $datas  = $finder->name('*.sql')->in($this->absoluteFixturesPath);

        $this->prepareCache($tmpdir);

        list($name, $defaultConfig) = $this->getConnection($input, $output);

        // Create a "sqldb.map" file
        $sqldbContent = '';
        foreach($datas as $data) {
            $output->writeln(sprintf('<info>Loading SQL fixtures from</info> <comment>%s</comment>.', $data));

            $sqldbContent .= $data->getFilename() . '=' . $name . PHP_EOL;
            $this->filesystem->copy($data, $tmpdir . '/fixtures/' . $data->getFilename(), true);
        }

        if ('' === $sqldbContent) {
            return -1;
        }

        $sqldbFile = $tmpdir . '/fixtures/sqldb.map';
        file_put_contents($sqldbFile, $sqldbContent);

        if (!$this->insertSql($defaultConfig, $tmpdir . '/fixtures', $tmpdir, $output)) {
            return -1;
        }

        $this->filesystem->remove($tmpdir);

        return 0;
    }

    /**
     * Prepare the cache directory
     *
     * @param string $tmpdir    The temporary directory path.
     */
    protected function prepareCache($tmpdir)
    {
        // Recreate a propel directory in cache
        $this->filesystem->remove($tmpdir);
        $this->filesystem->mkdir($tmpdir);

        $fixturesdir = $tmpdir . '/fixtures/';
        $this->filesystem->remove($fixturesdir);
        $this->filesystem->mkdir($fixturesdir);
    }

    /**
     * Insert SQL
     */
    protected function insertSql($config, $sqlDir, $schemaDir, $output)
    {
        // Insert SQL
        $ret = $this->callPhing('insert-sql', array(
            'propel.database.url'       => $config['connection']['dsn'],
            'propel.database.database'  => $config['adapter'],
            'propel.database.user'      => $config['connection']['user'],
            'propel.database.password'  => $config['connection']['password'],
            'propel.schema.dir'         => $schemaDir,
            'propel.sql.dir'            => $sqlDir,
        ));

        if (true === $ret) {
            $this->writeSection($output, array(
                '', 'All SQL statements have been executed.'
            ), 'fg=green;bg=black');
        } else {
            $this->writeTaskError($output, 'insert-sql', false);

            return false;
        }

        return true;
    }
}
