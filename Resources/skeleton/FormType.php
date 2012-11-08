<?php

namespace ##NAMESPACE##;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ##CLASS## extends AbstractType
{
    private $options = array(
        'data_class' => '##FQCN##',
    );

    public function set($name, $value)
    {
        $this->options[$name] = $value;
    }

    public function get($name)
    {
        return $this->options[$name];
    }

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
        $resolver->setDefaults($this->options);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return '##TYPE_NAME##';
    }
}
