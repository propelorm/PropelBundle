<?php

namespace ##NAMESPACE##;

use Propel\PropelBundle\Form\BaseAbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ##CLASS## extends BaseAbstractType
{
    protected $options = array(
        'data_class' => '##FQCN##',
        'name'       => '##TYPE_NAME##',
    );

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {##BUILD_CODE##
    }
}
