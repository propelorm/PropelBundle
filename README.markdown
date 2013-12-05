PropelBundle
============

[![Build Status](https://secure.travis-ci.org/propelorm/PropelBundle.png)](http://travis-ci.org/propelorm/PropelBundle)

This is the official implementation of [Propel](http://www.propelorm.org/) in Symfony2.

## Branching model

As `Propel2` will be released in the near future, we are migrating the branching model of this bundle in advance!

* The `1.0` branch contains Propel *1.6* integration for Symfony *2.0* (*currently 2.0 branch*).
* The `1.1` branch contains Propel *1.6* integration for Symfony *2.1* (*currently 2.1 branch*).
* The `1.2` branch contains Propel *1.6* integration for Symfony *>2.1* (*currently 2.2 and 2.3 *).
* The `1.4` branch contains Propel *1.6* integration for Symfony *>2.3* (*currently 2.4 and master branch*).
* The `2.0` branch will contain `Propel2` integration for Symfony *2.1*.
  We are still considering to integrate `Propel2` with Symfony *2.0*.
  In case, we will do so, there will be a `2.1` and `2.0` branch integrating the respective Symfony version!

**The 1.x branches are already available and you are encouraged to migrate your dependencies according to the listings!**

* If you depend on Symfony `2.4` or `master` branch, switch to the `1.4` branch.
* If you depend on Symfony `2.2` or `2.3`  branch, switch to the `1.2` branch.
* If you depend on Symfony `2.1` branch, switch to the `1.1` branch.
* If you depend on Symfony `2.0` branch, switch to the `1.0` branch.

**Note:** the `master`, and `2.0` branches won't be updated anymore, and will trigger a `E_USER_DEPRECATED` error to notice people.

## Features

 * Generation of model classes based on an XML schema (not YAML) placed under `BundleName/Resources/*schema.xml`;
 * Insertion of SQL statements;
 * Runtime autoloading of Propel and generated classes;
 * Propel runtime initialization through the XML configuration;
 * Migrations [Propel 1.6](http://www.propelorm.org/documentation/10-migrations.html);
 * Reverse engineering from [existing database](http://www.propelorm.org/wiki/Documentation/1.6/Existing-Database);
 * Integration to the Symfony2 Profiler;
 * Load SQL, YAML and XML fixtures;
 * Create/Drop databases;
 * Integration with the Form component;
 * Integration with the Security component;
 * Propel ParamConverter can be used with Sensio Framework Extra Bundle.

For documentation, see:

    Resources/doc/

[Read the documentation](https://github.com/propelorm/PropelBundle/blob/1.1/Resources/doc/index.markdown)

For license, see:

    Resources/meta/LICENSE
