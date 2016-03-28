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
     * @param string          $text   A text message.
     * @param string          $style  A style to apply on the section.
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
     * Asks a confirmation to the user.
     *
     * The question will be asked until the user answers by nothing, yes, or no.
     *
     * @param InputInterface  $input    An Input instance
     * @param OutputInterface $output   An Output instance
     * @param string|array    $question The question to ask
     * @param bool            $default  The default answer if the user enters nothing
     *
     * @return bool true if the user has confirmed, false otherwise
     */
    protected function askConfirmation(InputInterface $input, OutputInterface $output, $question, $default = true)
    {
        $question = new Question($question);
        do {
            $answer = $this->getHelperSet()->get('question')->ask($input, $output, $question);
        } while ($answer && !in_array(strtolower($answer[0]), array('y', 'n')));

        if (false === $default) {
            return $answer && 'y' == strtolower($answer[0]);
        }

        return !$answer || 'y' == strtolower($answer[0]);
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

        $this->writeSection($output, array(
            '[Propel] Error',
            '',
            'An error has occured during the "' . $taskName . '" task process.' . $moreText
        ), 'fg=white;bg=red');
    }
}
