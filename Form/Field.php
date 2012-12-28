<?php

namespace Propel\PropelBundle\Form;
 
class Field
{
    private $type, $options;

    function __construct($type = null, $options = array())
    {
        $this->setType($type);
        $this->setOptions($options);
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

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

}
