<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Bundle\PropelBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Propel\Bundle\PropelBundle\Form\EventListener\TranslationCollectionFormListener;

/**
 * form type for i18n-columns in propel
 *
 * @author Patrick Kaufmann
 */
class TranslationCollectionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!isset($options['entry_options']['data_class']) || null === $options['entry_options']['data_class']) {
            throw new MissingOptionsException('data_class must be set');
        }
        if (!isset($options['entry_options']['columns']) || null === $options['entry_options']['columns']) {
            throw new MissingOptionsException('columns must be set');
        }

        $listener = new TranslationCollectionFormListener($options['languages'], $options['entry_options']['data_class']);
        $builder->addEventSubscriber($listener);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(array(
            'languages'
        ));

        $resolver->setDefaults(array(
            'entry_type'    => TranslationType::class,
            'allow_add'     => false,
            'allow_delete'  => false,
            'entry_options' => array(
                'data_class'    => null,
                'columns'       => null
            )
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return CollectionType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'propel_translation_collection';
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }
}
