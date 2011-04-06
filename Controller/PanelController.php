<?php

namespace Propel\PropelBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerAware;

use Propel\PropelBundle\DataCollector\PropelDataCollector;

class PanelController extends ContainerAware
{
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
