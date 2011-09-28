<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Propel\PropelBundle\Form\ChoiceList\ModelChoiceList;
use \PropelCollection;
use \PropelObjectCollection;

class ModelsToArrayTransformer implements DataTransformerInterface
{
    private $choiceList;

    public function __construct(ModelChoiceList $choiceList)
    {
        $this->choiceList = $choiceList;
    }

    public function transform($collection)
    {
        if (null === $collection) {
            return array();
        }

        if (!($collection instanceof PropelCollection)) {
            throw new UnexpectedTypeException($collection, '\PropelCollection');
        }

        $array = array();

        if (count($this->choiceList->getIdentifier()) > 1) {
            $availableModels = $this->choiceList->getModels();

            foreach ($collection as $model) {
                $key = array_search($model, $availableModels);
                $array[] = $key;
            }
        } else {
            foreach ($collection as $model) {
                $array[] = current($this->choiceList->getIdentifierValues($model));
            }
        }

        return $array;
    }

    public function reverseTransform($keys)
    {
        $collection = new PropelObjectCollection();

        if ('' === $keys || null === $keys) {
            return $collection;
        }

        if (!is_array($keys)) {
            throw new UnexpectedTypeException($keys, 'array');
        }

        $notFound = array();

        foreach ($keys as $key) {
            if ($model = $this->choiceList->getModel($key)) {
                $collection->append($model);
            } else {
                $notFound[] = $key;
            }
        }

        if (count($notFound) > 0) {
            throw new TransformationFailedException(sprintf('The models with keys "%s" could not be found', implode('", "', $notFound)));
        }

        return $collection;
    }
}
