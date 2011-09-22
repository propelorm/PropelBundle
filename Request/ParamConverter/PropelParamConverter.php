<?php

namespace Propel\PropelBundle\Request\ParamConverter;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;


/**
 * PropelConverter.
 *
 * @author     Jérémie Augustin <jeremie.augustin@pixel-cookers.com>
 */
class PropelParamConverter implements ParamConverterInterface
{

    public function apply(Request $request, ConfigurationInterface $configuration)
    {
        $classQuery = $configuration->getClass() . 'Query';
        $options = $configuration->getOptions();

        if (!class_exists($classQuery)) {
            throw new \Exception(sprintf('The %s Query class does not exist', $classQuery));
        }

        // find by Pk
        if (false === $object = $this->findPk($classQuery, $request)) {
            throw new \LogicException('Unable to guess how to get a Propel object from the request information.');
            // find by criteria
            if (false === $object = $this->findOneBy($classQuery, $request, $options)) {
                throw new \LogicException('Unable to guess how to get a Propel object from the request information.');
            }
        }

        if (null === $object && false === $configuration->isOptional()) {
            throw new NotFoundHttpException(sprintf('%s object not found.', $configuration->getClass()));
        }

        $request->attributes->set($configuration->getName(), $object);
    }

    protected function findPk($classQuery, Request $request)
    {
        if (!$request->attributes->has('id')) {
            return false;
        }

        return $classQuery::create()->findPk($request->attributes->get('id'));
    }

    protected function findOneBy($classQuery, Request $request, $options)
    {
        $query = $classQuery::create();
        $hasCriteria = false;
        foreach ($request->attributes->all() as $key => $value) {
            try {
                $query->{'filterBy' . ucfirst($key)}($value);
                $hasCriteria = true;
            } catch (\PropelException $e) { }
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
        $options = $configuration->getOptions();
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
