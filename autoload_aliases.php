<?php

function autoload_propel_aliases($className) {
    if (0 === strpos($className, 'Propel\PropelBundle')) {
        class_alias(str_replace('Propel\PropelBundle', 'Propel\Bundle\PropelBundle', $className), $className);
    }
}

spl_autoload_register('autoload_propel_aliases', false, true);
