<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Command;

/**
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class Phing extends \Phing
{
    public static function getPhingVersion()
    {
        return 'Phing/Symfony';
    }

    /**
     * @see Phing
     */
    public function runBuild()
    {
        // workaround for included phing 2.3 which by default loads many tasks
        // that are not needed and incompatible (eg phing.tasks.ext.FtpDeployTask)
        // by placing current directory on the include path our defaults will be loaded
        // see ticket #5054
        $includePath = get_include_path();
        set_include_path(dirname(__FILE__).PATH_SEPARATOR.$includePath);
        parent::runBuild();
        set_include_path($includePath);
    }
}
