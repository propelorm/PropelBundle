PropelBundle
============

[![Build Status](https://travis-ci.org/propelorm/PropelBundle.svg?branch=2.0)](https://travis-ci.org/propelorm/PropelBundle)

This is the official implementation of [Propel](http://www.propelorm.org/) in Symfony 2.

## Branching model

As `Propel2` will be released in the near future, we are migrating the branching model of this bundle in advance!

* The `1.0` branch contains Propel *1.6* integration for Symfony *2.0* (*currently 2.0 branch*).
* The `1.1` branch contains Propel *1.6* integration for Symfony *2.1* (*currently 2.1 branch*).
* The `1.2` branch contains Propel *1.6* integration for Symfony *2.2* (*currently master branch*).
* The `2.0` branch contains `Propel2` integration for Symfony *2*.
* The `3.0` branch contains `Propel2` integration for Symfony *3*.

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
