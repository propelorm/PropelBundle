<?php

namespace Propel\PropelBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerAware;

use Propel\PropelBundle\DataCollector\PropelDataCollector;

/**
 * PanelController is designed to display information in the Propel Panel.
 *
 * @author William DURAND <william.durand1@gmail.com>
 */
class PanelController extends ContainerAware
{
    /**
     * This method renders the global Propel configuration.
     *
     * @param PropelDataCollector $collector  A PropelDataCollector collector
     */
    public function configurationAction(PropelDataCollector $collector)
    {
        $templating = $this->container->get('templating');

        return $templating->renderResponse(
            'PropelBundle:Panel:configuration.html.twig',
            array(
                'configuration'      => $this->container->get('propel.configuration')->getParameters(),
                'default_connection' => $this->container->getParameter('propel.dbal.default_connection'),
                'logging'            => $this->container->getParameter('propel.logging'),
                'charset'            => $this->container->getParameter('propel.charset'),
                'path'               => $this->container->getParameter('propel.path'),
                'phing_path'         => $this->container->getParameter('propel.phing_path'),
            )
        );
    }

}
