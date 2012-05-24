<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Response;

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
                'propel_version'     => \Propel::VERSION,
                'configuration'      => $this->container->get('propel.configuration')->getParameters(),
                'default_connection' => $this->container->getParameter('propel.dbal.default_connection'),
                'logging'            => $this->container->getParameter('propel.logging'),
                'path'               => $this->container->getParameter('propel.path'),
                'phing_path'         => $this->container->getParameter('propel.phing_path'),
            )
        );
    }

    /**
     * Renders the profiler panel for the given token.
     *
     * @param string  $token      The profiler token
     * @param string  $connection The connection name
     * @param integer $query
     *
     * @return Symfony\Component\HttpFoundation\Response A Response instance
     */
    public function explainAction($token, $connection, $query)
    {
        $profiler = $this->container->get('profiler');
        $profiler->disable();

        $profile = $profiler->loadProfile($token);
        $queries = $profile->getCollector('propel')->getQueries();

        if (!isset($queries[$query])) {
            return new Response('This query does not exist.');
        }

        // Open the connection
        $con = \Propel::getConnection($connection);

        // Get the adapter
        $db = \Propel::getDB($connection);

        try {
            $stmt = $db->doExplainPlan($con, $queries[$query]['sql']);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return new Response('<div class="error">This query cannot be explained.</div>');
        }

        return $this->container->get('templating')->renderResponse(
            'PropelBundle:Panel:explain.html.twig',
            array(
                'data' => $results,
                'query' => $query,
            )
        );
    }
}
