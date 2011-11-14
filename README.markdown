PropelBundle
============

[![Build Status](https://secure.travis-ci.org/propelorm/PropelBundle.png)](http://travis-ci.org/propelorm/PropelBundle)

This is the official implementation of [Propel](http://www.propelorm.org/) in Symfony2.

Currently supports:

 * Generation of model classes based on an XML schema (not YAML) placed under `BundleName/Resources/*schema.xml`.
 * Insertion of SQL statements.
 * Runtime autoloading of Propel and generated classes.
 * Propel runtime initialization through the XML configuration.
 * Migrations [Propel 1.6](http://www.propelorm.org/documentation/10-migrations.html).
 * Reverse engineering from [existing database](http://www.propelorm.org/wiki/Documentation/1.6/Existing-Database).
 * Integration to the Symfony2 Profiler.
 * Load SQL, YAML and XML fixtures.
 * Create/Drop databases.
 * Integration with the Form component.
 * Integration with the Security component.
 * Propel ParamConverter can be used with Sensio Framework Extra Bundle.

For documentation, see:

    Resources/doc/

[Read the documentation](https://github.com/propelorm/PropelBundle/blob/master/Resources/doc/README.markdown)

For license, see:

    Resources/meta/LICENSE
