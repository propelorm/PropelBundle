<?php

namespace Propel\PropelBundle\Form\Type;

use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Propel\PropelBundle\Form\ChoiceList\ModelChoiceList;
use Propel\PropelBundle\Form\EventListener\MergeCollectionListener;
use Propel\PropelBundle\Form\DataTransformer\ModelToIdTransformer;
use Propel\PropelBundle\Form\DataTransformer\ModelsToArrayTransformer;
use Symfony\Component\Form\AbstractType;

class ModelType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        if ($options['multiple']) {
            $builder->addEventSubscriber(new MergeCollectionListener())
                ->prependClientTransformer(new ModelsToArrayTransformer($options['choice_list']));
        } else {
            $builder->prependClientTransformer(new ModelToIdTransformer($options['choice_list']));
        }
    }

    public function getDefaultOptions(array $options)
    {
        $defaultOptions = array(
            'template'      => 'choice',
            'multiple'      => false,
            'expanded'      => false,
            'relation_map'  => null,
            'class'         => null,
            'property'      => null,
            'choices'       => array(),
            'preferred_choices' => array(),
            'multiple'      => false,
            'expanded'      => false,
        );

        $options = array_replace($defaultOptions, $options);

        if (!isset($options['choice_list'])) {
            $defaultOptions['choice_list'] = new ModelChoiceList(
                $options['relation_map'],
                $options['class'],
                $options['property'],
                $options['choices']
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
