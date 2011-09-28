<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormFactoryInterface;

use Propel\PropelBundle\Form\ChoiceList\ModelChoiceList;
use Propel\PropelBundle\Form\DataTransformer\ModelToIdTransformer;
use Propel\PropelBundle\Form\DataTransformer\ModelsToArrayTransformer;
use Propel\PropelBundle\Form\EventListener\MergeCollectionListener;

/**
 * ModelType class.
 *
 * @author William Durand <william.durand1@gmail.com>
 */
class ModelType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        if ($options['multiple']) {
            $builder->prependClientTransformer(new ModelsToArrayTransformer($options['choice_list']));
        } else {
            $builder->prependClientTransformer(new ModelToIdTransformer($options['choice_list']));
        }
    }

    public function getDefaultOptions(array $options)
    {
        $defaultOptions = array(
            'template'          => 'choice',
            'multiple'          => false,
            'expanded'          => false,
            'class'             => null,
            'property'          => null,
            'query'             => null,
            'choices'           => array(),
            'preferred_choices' => array(),
        );

        $options = array_replace($defaultOptions, $options);

        if (!isset($options['choice_list'])) {
            $defaultOptions['choice_list'] = new ModelChoiceList(
                $options['class'],
                $options['property'],
                $options['choices'],
                $options['query']
            );
        }

        return $defaultOptions;
    }

    public function getParent(array $options)
    {
        return 'choice';
    }

    public function getName()
    {
        return 'model';
    }
}