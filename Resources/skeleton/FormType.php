<?php

namespace ##NAMESPACE##;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class ##CLASS## extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilder $builder, array $options)
    {##BUILD_CODE##
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOptions()
    {
        return array(
            'data_class' => '##FQCN##',
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return '##TYPE_NAME##';
    }
}
