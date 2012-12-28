<?php

namespace /*#NAMESPACE#*/;

use Propel\PropelBundle\Form\BaseAbstractType;
use Propel\PropelBundle\Form\Field;

class /*#CLASS#*/ extends BaseAbstractType
{
    protected function setup()
    {
        $this->setOptions(array(
            'data_class' => '/*#FQCN#*/',
            'name'       => '/*#TYPE_NAME#*/',
        ));

        $this->setFields(array(/*#FIELDS#*/
        ));
    }
}
