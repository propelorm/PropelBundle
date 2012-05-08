The UniqueObjectValidator
=========================

In a form, if you want to validate the unicity of a field in a table you have to use the `UniqueObjectValidator`.
The only way to use it is in a `validation.yml` file, like this:

``` yaml
Acme\DemoBundle\Model\User:
    constraints:
        - Propel\PropelBundle\Validator\Constraints\UniqueObject: username
```

For validate the unicity of more than just one fields:

``` yaml
Acme\DemoBundle\Model\User:
    constraints:
        - Propel\PropelBundle\Validator\Constraints\UniqueObject: [username, login]
```

As many validator of this type as you want can be used.


[Back to index](index.markdown)
