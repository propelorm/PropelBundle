<?php

namespace ##NAMESPACE##;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ##CLASS## extends AbstractType
{
    private $options = array(
        'data_class' => '##FQCN##',
        'name'       => '##TYPE_NAME##',
    );

    public function setOption($name, $value)
    {
        $this->options[$name] = $value;
    }

    public function getOption($name)
    {
        return $this->options[$name];
    }

    public function getOptions()
    {
        return $this->options;
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
        return $this->getOption('name');
    }
}
