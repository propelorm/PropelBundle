<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Command;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * FixturesLoadCommand
 *
 * @author William DURAND <william.durand1@gmail.com>
 */
class FixturesLoadCommand extends AbstractCommand
{
    /**
     * Default fixtures directory.
     * @var string
     */
    private $defaultFixturesDir = 'propel/fixtures';

    /**
     * Absolute path for fixtures directory
     * @var string
     */
    private $absoluteFixturesPath = '';

    /**
     * Filesystem for manipulating files
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    private $filesystem = null;

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('propel:fixtures:load')
            ->setDescription('Load XML, SQL and/or YAML fixtures')
            ->setHelp(<<<EOT
The <info>propel:fixtures:load</info> loads <info>XML</info>, <info>SQL</info> and/or <info>YAML</info> fixtures.

  <info>php app/console propel:fixtures:load</info>

The <info>--connection</info> parameter allows you to change the connection to use.
The default connection is the active connection (propel.dbal.default_connection).

The <info>--dir</info> parameter allows you to change the directory that contains <info>XML</info> or/and <info>SQL</info> fixtures files <comment>(default: propel/fixtures)</comment>.

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

            ->addArgument('bundle', InputArgument::OPTIONAL, 'The bundle to load fixtures from')
            ->addOption(
                'dir', 'd', InputOption::VALUE_OPTIONAL,
                'The directory where XML, SQL and/or YAML fixtures files are located',
                $this->defaultFixturesDir
            )
            ->addOption('xml', '', InputOption::VALUE_NONE, 'Load XML fixtures')
            ->addOption('sql', '', InputOption::VALUE_NONE, 'Load SQL fixtures')
            ->addOption('yml', '', InputOption::VALUE_NONE, 'Load YAML fixtures')
            ->addOption('connection', null, InputOption::VALUE_OPTIONAL, 'Set this parameter to define a connection to use')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->filesystem = new Filesystem();

        if (null !== $this->bundle) {
            $this->absoluteFixturesPath = $this->getFixturesPath($this->bundle);
        } else {
            $this->absoluteFixturesPath = realpath($this->getApplication()->getKernel()->getProjectDir() . '/' . $input->getOption('dir'));
        }

        if (!$this->absoluteFixturesPath && !file_exists($this->absoluteFixturesPath)) {
            $this->writeSection($output, array(
                'The fixtures directory "' . $this->absoluteFixturesPath . '" does not exist.'
            ), 'fg=white;bg=red');

            return \Propel\Generator\Command\AbstractCommand::CODE_ERROR;
        }

        $noOptions = !$input->getOption('xml') && !$input->getOption('sql') && !$input->getOption('yml');

        if ($input->getOption('sql') || $noOptions) {
            if (-1 === $this->loadSqlFixtures($input, $output)) {
                $output->writeln('No <info>SQL</info> fixtures found.');
            }
        }

        if ($input->getOption('xml') || $noOptions) {
            if (-1 === $this->loadFixtures($input, $output, 'xml')) {
                $output->writeln('No <info>XML</info> fixtures found.');
            }
        }

        if ($input->getOption('yml') || $noOptions) {
            if (-1 === $this->loadFixtures($input, $output, 'yml')) {
                $output->writeln('No <info>YML</info> fixtures found.');
            }
        }

        return \Propel\Generator\Command\AbstractCommand::CODE_SUCCESS;
    }

    /**
     * Load fixtures
     *
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param string                                            $type   If specified, only fixtures with the given type will be loaded (yml, xml).
     */
    protected function loadFixtures(InputInterface $input, OutputInterface $output, $type = null)
    {
        if (null === $type) {
            return;
        }

        $datas = $this->getFixtureFiles($type);

        if (count(iterator_to_array($datas)) === 0) {
            return -1;
        }

        $connectionName = $input->getOption('connection') ?: $this->getDefaultConnection();

        if ('yml' === $type) {
            $loader = $this->getContainer()->get('propel.loader.yaml');
        } elseif ('xml' === $type) {
            $loader = $this->getContainer()->get('propel.loader.xml');
        } else {
            return;
        }

        $nb = $loader->load(iterator_to_array($datas), $connectionName);

        $output->writeln(sprintf('<comment>%s</comment> %s fixtures file%s loaded.', $nb, strtoupper($type), $nb > 1 ? 's' : ''));

        return true;
    }

    /**
     * Load SQL fixtures
     *
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function loadSqlFixtures(InputInterface $input, OutputInterface $output)
    {
        $tmpdir = $this->getCacheDir();
        $datas  = $this->getFixtureFiles('sql');

        $this->prepareCache($tmpdir);

        $connectionName = $input->getOption('connection') ?: $this->getContainer()->getParameter('propel.dbal.default_connection');

        // Create a "sqldb.map" file
        $sqldbContent = '';
        foreach ($datas as $data) {
            $output->writeln(sprintf('<info>Loading SQL fixtures from</info> <comment>%s</comment>.', $data));

            $sqldbContent .= $data->getFilename() . '=' . $connectionName . PHP_EOL;
            $this->filesystem->copy($data, $tmpdir . '/' . $data->getFilename(), true);
        }

        if ('' === $sqldbContent) {
            return -1;
        }

        $sqldbFile = $tmpdir . '/sqldb.map';
        file_put_contents($sqldbFile, $sqldbContent);

        if (!$this->insertSql($connectionName, $input, $output)) {
            return -1;
        }

        $this->filesystem->remove($tmpdir);

        return 0;
    }

    /**
     * Prepare the cache directory
     *
     * @param string $tmpdir The temporary directory path.
     */
    protected function prepareCache($tmpdir)
    {
        // Recreate a propel directory in cache
        $this->filesystem->remove($tmpdir);
        $this->filesystem->mkdir($tmpdir);
    }

    /**
     * Insert SQL
     */
    protected function insertSql($connectionName, InputInterface $input, OutputInterface $output)
    {
        $parameters = array(
            '--connection'  => array($connectionName),
            '--verbose'     => $input->getOption('verbose'),
            '--sql-dir'     => $this->getCacheDir(),
            '--force'       => 'force'
        );

        // add the command's name to the parameters
        array_unshift($parameters, $this->getName());

        $command = $this->getApplication()->find('propel:sql:insert');
        $command->setApplication($this->getApplication());

        // and run the sub-command
        $ret = $command->run(new ArrayInput($parameters), $output);

        if ($ret === 0) {
            $output->writeln('All SQL statements have been inserted.');
        } else {
            $this->writeTaskError($output, 'insert-sql', false);
        }

        return $ret === 0;
    }

    /**
     * Returns the fixtures files to load.
     *
     * @param string $type The extension of the files.
     * @param string $in   The directory in which we search the files. If null,
     *                     we'll use the absoluteFixturesPath property.
     *
     * @return \Iterator An iterator through the files.
     */
    protected function getFixtureFiles($type = 'sql', $in = null)
    {
        $finder = new Finder();
        $finder->sort(function ($a, $b) {
            return strcmp($a->getPathname(), $b->getPathname());
        })->name('*.' . $type);

        $files = $finder->in(null !== $in ? $in : $this->absoluteFixturesPath);

        if (null === $this->bundle) {
            return $files;
        }

        $finalFixtureFiles = array();
        foreach ($files as $file) {
            $fixtureFilePath = str_replace($this->getFixturesPath($this->bundle) . DIRECTORY_SEPARATOR, '', $file->getRealPath());
            $logicalName = sprintf('@%s/Resources/fixtures/%s', $this->bundle->getName(), $fixtureFilePath);
            $finalFixtureFiles[] = new \SplFileInfo($this->getFileLocator()->locate($logicalName));
        }

        return new \ArrayIterator($finalFixtureFiles);
    }

    /**
     * Returns the path the command will look into to find fixture files
     *
     * @param BundleInterface $bundle The bundle to explore.
     *
     * @return String
     */
    protected function getFixturesPath(BundleInterface $bundle)
    {
        return $bundle->getPath() . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR . 'fixtures';
    }

    /**
     * @return \Symfony\Component\Config\FileLocatorInterface
     */
    protected function getFileLocator()
    {
        return $this->getContainer()->get('file_locator');
    }
}
