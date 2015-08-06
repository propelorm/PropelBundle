<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\PropelBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Propel\PropelBundle\Form\EventListener\TranslationFormListener;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Translation type class
 *
 * @author Patrick Kaufmann
 */
class TranslationType extends AbstractType
{
    /**
      * {@inheritdoc}
      */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(
            new TranslationFormListener($options['columns'], $options['data_class'])
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'propel_translation';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(array(
            'data_class',
            'columns'
        ));
    }

    // BC for SF < 2.7
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $this->configureOptions($resolver);
    }
}
