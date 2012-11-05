The UniqueObjectValidator
=========================

In a form, if you want to validate the uniqueness of a field in a table you have to use the `UniqueObjectValidator`.

You may use as many validators of this type as you want.

YAML
----

You can specify this using the `validation.yml` file, like this:

``` yaml
Acme\DemoBundle\Model\User:
    constraints:
        - Propel\PropelBundle\Validator\Constraints\UniqueObject: username
```

If you want to validate the uniqueness of more than just one field:

``` yaml
Acme\DemoBundle\Model\User:
    constraints:
        - Propel\PropelBundle\Validator\Constraints\UniqueObject: [username, login]
```

PHP
---

You can also specify this using php. Fields can be specified as a string if there is only one field

``` php
use Propel\PropelBundle\Validator\Constraint\UniqueObject;

...

    /**
     * Load the Validation Constraints
     *
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addConstraint(
            new UniqueObject(
                array(
                    'fields' => 'username',
                    'message' => 'We already have a user with {{ fields }}'
                )
            )
        );
    }
```    

If there is more than one field you must use an array

``` php

...

        $metadata->addConstraint(
            new UniqueObject(
                array(
                    'fields' => array('username', 'login')
                    'message' => 'We already have a user with {{ fields }}'
                )
            )
        );
        
...

```






[Back to index](index.markdown)
