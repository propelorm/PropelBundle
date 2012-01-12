<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Controller;

use Symfony\Bridge\Propel1\DataCollector\PropelDataCollector;
use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * PanelController is designed to display information in the Propel Panel.
 *
 * @author William DURAND <william.durand1@gmail.com>
 */
class PanelController extends ContainerAware
{
    /**
     * This method renders the global Propel configuration.
     */
    public function configurationAction()
    {
        $templating = $this->container->get('templating');

        return $templating->renderResponse(
            'PropelBundle:Panel:configuration.html.twig',
            array(
                'configuration'      => $this->container->get('propel.configuration')->getParameters(),
                'default_connection' => $this->container->getParameter('propel.dbal.default_connection'),
                'logging'            => $this->container->getParameter('propel.logging'),
                'path'               => $this->container->getParameter('propel.path'),
                'phing_path'         => $this->container->getParameter('propel.phing_path'),
            )
        );
    }

}
