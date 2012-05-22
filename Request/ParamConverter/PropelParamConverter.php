<?php

namespace Propel\PropelBundle\Request\ParamConverter;

use Propel\PropelBundle\Util\PropelInflector;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;


/**
 * PropelParamConverter
 *
 * This convert action parameter to a Propel Object
 * there is two option for this converter:
 *
 * mapping : take an array of routeParam => column
 * exclude : take an array of routeParam to exclude from the conversion process
 *
 *
 * @author     Jérémie Augustin <jeremie.augustin@pixel-cookers.com>
 */
class PropelParamConverter implements ParamConverterInterface
{
    /**
     * the pk column (e.g. id)
     * @var string
     */
    protected $pk;

    /**
     * list of column/value to use with filterBy
     * @var array
     */
    protected $filters = array();

    /**
     * list of route parameters to exclude from the conversion process
     * @var array
     */
    protected $exclude = array();

    public function apply(Request $request, ConfigurationInterface $configuration)
    {
        $classQuery = $configuration->getClass() . 'Query';
        $classPeer = $configuration->getClass() . 'Peer';

        if (!class_exists($classQuery)) {
            throw new \Exception(sprintf('The %s Query class does not exist', $classQuery));
        }

        $tableMap = $classPeer::getTableMap();
        $pkColumns = $tableMap->getPrimaryKeyColumns();

        if (count($pkColumns) == 1) {
            $this->pk = strtolower($pkColumns[0]->getName());
        }

        $options = $configuration->getOptions();

        if (isset($options['mapping'])) {
            // We use the mapping for calling findPk or filterBy
            foreach ($options['mapping'] as $routeParam => $column) {
                if ($request->attributes->has($routeParam)) {
                    if ($this->pk === $column) {
                        $this->pk = $routeParam;
                    } else {
                        $this->filters[$column] = $request->attributes->get($routeParam);
                    }
                }
            }
        } else {
            $this->exclude = isset($options['exclude'])? $options['exclude'] : array();
            $this->filters = $request->attributes->all();
        }

        // find by Pk
        if (false === $object = $this->findPk($classQuery, $request)) {
            // find by criteria
            if (false === $object = $this->findOneBy($classQuery, $request)) {
                if ($configuration->isOptional()) {
                    //we find nothing but the object is optional
                    $object = null;
                } else {
                    throw new \LogicException('Unable to guess how to get a Propel object from the request information.');
                }
            }
        }

        if (null === $object && false === $configuration->isOptional()) {
            throw new NotFoundHttpException(sprintf('%s object not found.', $configuration->getClass()));
        }

        $request->attributes->set($configuration->getName(), $object);

        return true;
    }

    protected function findPk($classQuery, Request $request)
    {
        if (in_array($this->pk, $this->exclude) || !$request->attributes->has($this->pk)) {
            return false;
        }

        return $classQuery::create()->findPk($request->attributes->get($this->pk));
    }

    protected function findOneBy($classQuery, Request $request)
    {
        $query = $classQuery::create();
        $hasCriteria = false;
        foreach ($this->filters as $column => $value) {
            if (!in_array($column, $this->exclude)) {
                try {
                    $query->{'filterBy' . PropelInflector::camelize($column)}($value);
                    $hasCriteria = true;
                } catch (\PropelException $e) { }
            }
        }

        if (!$hasCriteria) {
            return false;
        }

        return $query->findOne();
    }

    public function supports(ConfigurationInterface $configuration)
    {
        if (null === ($classname = $configuration->getClass())) {
            return false;
        }
        if (!class_exists($classname)) {
            return false;
        }
        // Propel Class?
        $class = new \ReflectionClass($configuration->getClass());
        if ($class->isSubclassOf('BaseObject')) {
            return true;
        }

        return false;
    }
}
