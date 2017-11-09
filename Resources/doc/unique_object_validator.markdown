The UniqueObjectValidator
=========================

In a form, if you want to validate the uniqueness of a field in a table you have to use the `UniqueObjectValidator`.

You may use as many validators of this type as you want.

The validator has 1 required parameter:

* `fields` : a field or an array of fields to test for uniqueness

and 3 optionals parameters:

* `message` : the error message with two variable `{{ object_class }}` and `{{ fields }}`
* `messageFieldSeparator` : the field separator ` and `
* `errorPath` : the relative path where the error will be attached, if none is set the error is global.


YAML
----

You can specify this using the `validation.yml` file, like this:

``` yaml
Acme\DemoBundle\Model\User:
    constraints:
        - Propel\PropelBundle\Validator\Constraints\UniqueObject: 
            fields:  username
```

If you want to validate the uniqueness of more than just one field:

``` yaml
Acme\DemoBundle\Model\User:
    constraints:
        - Propel\PropelBundle\Validator\Constraints\UniqueObject: 
            fields: [username, login]
```

Full configuration :

``` yaml
Acme\DemoBundle\Model\User:
    constraints:
        - Propel\PropelBundle\Validator\Constraints\UniqueObject: 
            fields: [username, login]
            message: We already have a user with {{ fields }}
            messageFieldSeparator: " and "
            errorPath: username
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
                    'message' => 'We already have a user with {{ fields }}',
                    'messageFieldSeparator' => ' and '
                    'errorPath' => 'username',
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
                    'fields' => array('username', 'login'),
                    'message' => 'We already have a user with {{ fields }}',
                    'messageFieldSeparator' => ' and ',
                    'errorPath' => 'username'
                )
            )
        );
        
...

```


XML
---

You can also specify this using xml

```xml

    <class name="Acme\DemoBundle\Model\User">
        
        <constraint name="Propel\PropelBundle\Validator\Constraints\UniqueObject">
            <option name="fields">username</option>
            <option name="message">We already have a user with {{ fields }}</option>
            <option name="messageFieldSeparator"> and </option>
            <option name="errorPath">username</option>
        </constraint>
        
    </class>
```


[Back to index](index.markdown)
