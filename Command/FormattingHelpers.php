<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Command;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
trait FormattingHelpers
{
    /**
     * Comes from the SensioGeneratorBundle.
     * @see https://github.com/sensio/SensioGeneratorBundle/blob/master/Command/Helper/DialogHelper.php#L52
     *
     * @param OutputInterface $output The output.
     * @param string $text A text message.
     * @param string $style A style to apply on the section.
     */
    protected function writeSection(OutputInterface $output, $text, $style = 'bg=blue;fg=white')
    {
        $output->writeln(array(
            '',
            $this->getHelperSet()->get('formatter')->formatBlock($text, $style, true),
            '',
        ));
    }

    /**
     * Ask confirmation from the user.
     *
     * @param OutputInterface $output The output.
     * @param string $question A given question.
     * @param string $default A default response.
     */
    protected function askConfirmation(OutputInterface $output, $question, $default = null)
    {
        return $this->getHelperSet()->get('dialog')->askConfirmation($output, $question, $default);
    }

    /**
     * @param OutputInterface $output   The output.
     * @param string          $filename The filename.
     */
    protected function writeNewFile(OutputInterface $output, $filename)
    {
        $output->writeln('>>  <info>File+</info>    ' . $filename);
    }

    /**
     * @param OutputInterface $output    The output.
     * @param string          $directory The directory.
     */
    protected function writeNewDirectory(OutputInterface $output, $directory)
    {
        $output->writeln('>>  <info>Dir+</info>     ' . $directory);
    }

    /**
     * Renders an error message if a task has failed.
     *
     * @param OutputInterface $output   The output.
     * @param string          $taskName A task name.
     * @param Boolean         $more     Whether to add a 'more details' message or not.
     */
    protected function writeTaskError($output, $taskName, $more = true)
    {
        $moreText = $more ? ' To get more details, run the command with the "--verbose" option.' : '';

        return $this->writeSection($output, array(
            '[Propel] Error',
            '',
            'An error has occured during the "' . $taskName . '" task process.' . $moreText
        ), 'fg=white;bg=red');
    }
}
