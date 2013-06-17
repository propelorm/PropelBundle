<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

use Propel\PropelBundle\Command\AbstractCommand;

/**
 * SqlBuildCommand.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author William DURAND <william.durand1@gmail.com>
 */
class SqlBuildCommand extends AbstractCommand
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
            ->addOption('connection', null, InputOption::VALUE_OPTIONAL, 'Set this parameter to define a connection to use')
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
        $finder = new Finder();
        $filesystem = new Filesystem();

        $sqlDir = $this->getApplication()->getKernel()->getRootDir(). DIRECTORY_SEPARATOR . 'propel'. DIRECTORY_SEPARATOR . 'sql';
        $cacheDir = $this->getApplication()->getKernel()->getCacheDir(). DIRECTORY_SEPARATOR . 'sql';

        $filesystem->remove($cacheDir);
        $filesystem->mkdir($cacheDir);

        if (!$filesystem->exists($sqlDir)) {
            $filesystem->mkdir($sqlDir);
        }

        // Execute the task
        $ret = $this->callPhing('build-sql', array(
            'propel.sql.dir' => $cacheDir
        ));

        // Show the list of generated files
        if (true === $ret) {
            $files = $finder->name('*')->in($cacheDir);

            $nbFiles = 0;
            foreach ($files as $file) {
                $fileExt = pathinfo($file->getFilename(), PATHINFO_EXTENSION);
                $finalLocation = $sqlDir. DIRECTORY_SEPARATOR. $file->getFilename();

                if ($fileExt === 'map' && $filesystem->exists($finalLocation)) {
                    $this->mergeMapFiles($finalLocation, (string) $file);
                } else {
                    $filesystem->remove($finalLocation);
                    $filesystem->rename((string) $file, $finalLocation);
                }

                $this->writeNewFile($output, (string) $file);

                if ('sql' === $fileExt) {
                    $nbFiles++;
                }
            }

            $output->writeln(sprintf('<comment>%d</comment> <info>SQL file%s ha%s been generated.</info>',
                $nbFiles, $nbFiles > 1 ? 's' : '', $nbFiles > 1 ? 've' : 's'
            ));
        } else {
            $this->writeSection($output, array(
                '[Propel] Error',
                '',
                'An error has occured during the "propel:sql:build" command process. To get more details, run the command with the "--verbose" option.'
            ), 'fg=white;bg=red');
        }
    }

    /**
     * Reads the existing target and the generated map files, and adds to the
     * target the missing lines that are in the generated file.
     *
     * @param string $target    target map filename
     * @param string $generated generated map filename
     *
     * @return boolean result
     */
    protected function mergeMapFiles($target, $generated)
    {
        if(($targetContent = file($target)) === false)

            return false;
        if(($generatedContent = file($generated)) === false)

            return false;

        $targetContent = array_merge($generatedContent, array_diff($targetContent, $generatedContent));

        return file_put_contents($target, $targetContent);
    }
}
