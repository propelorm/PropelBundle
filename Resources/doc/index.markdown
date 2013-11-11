PropelBundle
============

This is the official implementation of [Propel](http://www.propelorm.org/) in Symfony2.


## Installation ##

The recommended way to install this bundle is to rely on [Composer](http://getcomposer.org):

``` javascript
{
    "require": {
        // ...
        "propel/propel-bundle": "1.1.*"
    }
}
```

Otherwise you can use Git, SVN, Git submodules, or the Symfony vendor management (deps file):

 * Clone this bundle in the `vendor/bundles/Propel` directory:

    > git submodule add https://github.com/propelorm/PropelBundle.git vendor/bundles/Propel/PropelBundle

 * Checkout Propel and Phing in the `vendor` directory:

    > svn checkout http://svn.github.com/propelorm/Propel.git vendor/propel

    > svn checkout http://svn.phing.info/tags/2.4.6/ vendor/phing

 * Instead of using svn, you can clone the unofficial Git repositories:

    > git submodule add https://github.com/phingofficial/phing.git vendor/phing

    > git submodule add https://github.com/propelorm/Propel.git vendor/propel

 * Instead of doing this manually, you can use the Symfony vendor management via the deps file:

   See http://www.propelorm.org/cookbook/symfony2/working-with-symfony2.html#via_symfony2_vendor_management

   If you are using a Symfony2 2.x.x version (actually, a version which is not 2.1 or above), be sure to deps.lock the PropelBundle to a commit on the 2.0 branch,
   which does not use the Bridge


The second step is to register this bundle in the `AppKernel` class:

``` php
public function registerBundles()
{
    $bundles = array(
        // ...
        new Propel\PropelBundle\PropelBundle(),
    );

    // ...
}
```

Don't forget to register the PropelBundle namespace in `app/autoload.php` if you are not using Composer:

``` php
$loader->registerNamespaces(array(
    // ...
    'Propel' => __DIR__.'/../vendor/bundles',
));
$loader->registerPrefixes(array(
    // ...
    'Phing'  => __DIR__.'/../vendor/phing/classes/phing',
));
```

You are almost ready, the next steps are:

* to [configure the bundle](configuration.markdown);
* to [configure Propel](propel_configuration.markdown);
* to [write an XML schema](schema.markdown).

Now, you can build your model classes, and SQL by running the following command:

    > php app/console propel:build [--classes] [--sql] [--insert-sql] [--connection[=""]]

To insert SQL statements, use the `propel:sql:insert` command:

    > php app/console propel:sql:insert [--force] [--connection[=""]]

Note that the `--force` option is needed to actually execute the SQL statements.

Congratulation! You're done, just use the Model classes as any other class in Symfony2:

``` php
<?php

class HelloController extends Controller
{
    public function indexAction($name)
    {
        $author = new \Acme\DemoBundle\Model\Author();
        $author->setFirstName($name);
        $author->save();

        return $this->render('AcmeDemoBundle:Hello:index.html.twig', array(
            'name' => $name, 'author' => $author)
        );
    }
}
```

Now you can read more about:

* [The ACL Implementation](acl.markdown);
* [The Commands](commands.markdown);
* [The Fixtures](fixtures.markdown);
* [The PropelParamConverter](param_converter.markdown);
* [The UniqueObjectValidator](unique_object_validator.markdown).
* [The ModelTranslation](model_translation.markdown).


## Bundle Inheritance ##

The `PropelBundle` makes use of the bundle inheritance. Currently only schema inheritance is provided.

### Schema Inheritance ###

You can override the defined schema of a bundle from within its child bundle.
To make use of the inheritance you only need to drop a schema file in the `Resources/config` folder of the child bundle.

Each file can be overridden without interfering with other schema files.
If you want to remove parts of a schema, you only need to add an empty schema file.
