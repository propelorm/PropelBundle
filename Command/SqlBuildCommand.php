<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

use Propel\PropelBundle\Command\AbstractPropelCommand;

/**
 * SqlBuildCommand.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author William DURAND <william.durand1@gmail.com>
 */
class SqlBuildCommand extends AbstractPropelCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDescription('Build the SQL generation code for all tables based on Propel XML schemas')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command builds the SQL table generation code based on the XML schemas defined in all Bundles.

  <info>php %command.full_name%</info>
EOT
            )
            ->setName('propel:sql:build')
        ;
    }

    /**
     * @see Command
     *
     * @throws \InvalidArgumentException When the target directory does not exist
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->writeSection($output, '[Propel] You are running the command: propel:sql:build');

        if ($input->getOption('verbose')) {
            $this->additionalPhingArgs[] = 'verbose';
        }

        $finder = new Finder();
        $filesystem = new Filesystem();

        $sqlDir = $this->getApplication()->getKernel()->getRootDir(). DIRECTORY_SEPARATOR . 'propel'. DIRECTORY_SEPARATOR . 'sql';

        $filesystem->remove($sqlDir);
        $filesystem->mkdir($sqlDir);

        // Execute the task
        $ret = $this->callPhing('build-sql', array(
            'propel.sql.dir' => $sqlDir
        ));

        if (true === $ret) {
            $files = $finder->name('*')->in($sqlDir);

            $nbFiles = 0;
            foreach ($files as $file) {
                $this->writeNewFile($output, (string) $file);

                if ('sql' === pathinfo($file->getFilename(), PATHINFO_EXTENSION)) {
                    $nbFiles++;
                }
            }

            $this->writeSection(
                $output,
                sprintf('<comment>%d</comment> <info>SQL file%s ha%s been generated.</info>',
                    $nbFiles, $nbFiles > 1 ? 's' : '', $nbFiles > 1 ? 've' : 's'
                ),
                'bg=black'
            );
        } else {
            $this->writeSection($output, array(
                '[Propel] Error',
                '',
                'An error has occured during the "%command.name%" command process. To get more details, run the command with the "--verbose" option.'
            ), 'fg=white;bg=red');
        }
    }
}
