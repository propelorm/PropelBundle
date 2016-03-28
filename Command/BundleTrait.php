<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * @author Moritz Schroeder <moritz.schroeder@molabs.de>
 */
trait BundleTrait
{
    /**
     * @return ContainerInterface
     */
    protected abstract function getContainer();

    /**
     * Returns the selected bundle.
     * If no bundle argument is set, the user will get ask for it.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return BundleInterface
     */
    protected function getBundle(InputInterface $input, OutputInterface $output)
    {
        $kernel = $this
            ->getContainer()
            ->get('kernel');

        if ($input->hasArgument('bundle') && '@' === substr($input->getArgument('bundle'), 0, 1)) {
            return $kernel->getBundle(substr($input->getArgument('bundle'), 1));
        }

        $bundleNames = array_keys($kernel->getBundles());

        do {
            $question = '<info>Select the bundle</info>: ';
            $question = new Question($question);
            $question->setAutocompleterValues($bundleNames);

            $bundleName = $this->getHelperSet()->get('question')->ask($input, $output, $question);

            if (in_array($bundleName, $bundleNames)) {
                break;
            }
            $output->writeln(sprintf('<bg=red>Bundle "%s" does not exist.</bg>', $bundleName));
        } while (true);

        return $kernel->getBundle($bundleName);
    }
}