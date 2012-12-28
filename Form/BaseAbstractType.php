<?php

namespace Propel\PropelBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

abstract class BaseAbstractType extends AbstractType
{
    protected $options = array(
        'name' => '',
    );

    /** @var Field[] */
    protected $fields = array();

    function __construct($mergeOptions = null)
    {
        if ($mergeOptions) {
            $this->mergeOptions($mergeOptions);
        }
    }

    protected function setup()
    {

    }

    protected function configure()
    {

    }

    public function setOption($name, $value)
    {
        $this->options[$name] = $value;
        return $this;
    }

    public function getOption($name)
    {
        return $this->options[$name];
    }

    public function setOptions($options)
    {
        $this->options = $options;
        return $this;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function mergeOptions($options)
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    public function setField($name, Field $field)
    {
        $this->fields[$name] = $field;
        return $this;
    }

    public function setFields($fields)
    {
        foreach($fields as $name => $field) {
            $this->setField($name, $field);
        }
        return $this;
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function getField($name)
    {
        return $this->fields[$name];
    }

    public function removeField($name)
    {
        unset($this->fields[$name]);
        return $this;
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

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->setup();
        $this->configure();
        foreach($this->getFields() as $name => $field) {
            $builder->add($name, $field->getType(), $field->getOptions());
        }
    }

}
