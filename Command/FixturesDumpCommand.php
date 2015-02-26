<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Command;

use Propel\PropelBundle\Command\AbstractCommand;
use Propel\PropelBundle\DataFixtures\Dumper\YamlDataDumper;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * FixturesDumpCommand.
 *
 * @author William DURAND <william.durand1@gmail.com>
 */
class FixturesDumpCommand extends AbstractCommand
{
    /**
     * Default fixtures directory.
     * @var string
     */
    private $defaultFixturesDir = 'app/propel/fixtures';

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDescription('Dump data from database into YAML fixtures file.')
            ->addOption('connection', null, InputOption::VALUE_OPTIONAL, 'Set this parameter to define a connection to use')
            ->setHelp(<<<EOT
The <info>%command.name%</info> dumps data from database into YAML fixtures file.

  <info>php %command.full_name%</info>

The <info>--connection</info> parameter allows you to change the connection to use.
The default connection is the active connection (propel.dbal.default_connection).
EOT
            )
            ->setName('propel:fixtures:dump')
        ;
    }

    /**
     * @see Command
     *
     * @throws \InvalidArgumentException When the target directory does not exist
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->writeSection($output, '[Propel] You are running the command: propel:fixtures:dump');

        list($name, $defaultConfig) = $this->getConnection($input, $output);

        $path     = realpath($this->getApplication()->getKernel()->getRootDir() . '/../') . '/' . $this->defaultFixturesDir;
        $filename = $path . '/fixtures_' . time() . '.yml';

        $dumper = new YamlDataDumper($this->getApplication()->getKernel()->getRootDir());

        try {
            $dumper->dump($filename, $name);
        } catch (\Exception $e) {
            $this->writeSection($output, array(
                '[Propel] Exception',
                '',
                $e->getMessage()), 'fg=white;bg=red');

            return false;
        }

        $output->writeln('');
        $output->writeln(sprintf('>>  <info>File+</info>    %s', $filename));

        return true;
    }
}
