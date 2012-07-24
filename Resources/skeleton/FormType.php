<?php

namespace ##NAMESPACE##;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ##CLASS## extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {##BUILD_CODE##
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => '##FQCN##',
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return '##TYPE_NAME##';
    }
}
