PropelBundle
============

**DEPRECATED: This repo is not maintained anymore. Please use https://github.com/SkyFoxvn/PropelBundle instead.**



This is the official implementation of [Propel](http://www.propelorm.org/) in Symfony.

## Branching model

As `Propel2` will be released in the near future, we are migrating the branching model of this bundle in advance!

* The `1.6` branch contains Propel *1.6* integration for Symfony *^2.8* (*currently master branch*). [EOL]
* The `3.0` branch contains [Propel2](https://github.com/propelorm/Propel2/) integration for Symfony *2.8-3.x*. [EOL]
* The `4.0` branch contains [Propel2](https://github.com/propelorm/Propel2/) integration for Symfony *3.4-4.x*.

## Features

 * Generation of model classes based on an XML schema (not YAML) placed under `BundleName/Resources/*schema.xml`;
 * Insertion of SQL statements;
 * Runtime autoloading of Propel and generated classes;
 * Propel runtime initialization through the XML configuration;
 * [Propel Migrations](http://propelorm.org/documentation/09-migrations.html);
 * Reverse engineering from [existing database](http://propelorm.org/documentation/cookbook/working-with-existing-databases.html);
 * Integration to the Symfony Profiler;
 * Load SQL, YAML and XML fixtures;
 * Create/Drop databases;
 * Integration with the Form component;
 * Integration with the Security component;
 * Propel ParamConverter can be used with Sensio Framework Extra Bundle.

[Read the documentation](http://propelorm.org/documentation/)

For license, see:

    Resources/meta/LICENSE
