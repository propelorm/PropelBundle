PropelBundle
============

[![Build Status](https://travis-ci.org/propelorm/PropelBundle.svg?branch=1.5)](https://travis-ci.org/propelorm/PropelBundle)
[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/propelorm/PropelBundle?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

This is the official implementation of [Propel](http://www.propelorm.org/Propel/) in Symfony.

## Branching model

### Propel1 integration

The two major branches being supported are:

* The [1.5](https://github.com/propelorm/PropelBundle/tree/1.5) branch contains Propel *1.6+* integration for Symfony *2.8 LTS*. [![Build Status](https://travis-ci.org/propelorm/PropelBundle.svg?branch=1.5)](https://travis-ci.org/propelorm/PropelBundle)
* The [1.6](https://github.com/propelorm/PropelBundle/tree/1.6) branch contains Propel *1.6+* integration for Symfony *3.x*. [![Build Status](https://travis-ci.org/propelorm/PropelBundle.svg?branch=1.6)](https://travis-ci.org/propelorm/PropelBundle)

If you are running on an older version, you may require one of the following versions of this bundle.

* The [1.0](https://github.com/propelorm/PropelBundle/tree/1.0) branch contains Propel *1.6* integration for Symfony *2.0*.
* The [1.1](https://github.com/propelorm/PropelBundle/tree/1.1) branch contains Propel *1.6* integration for Symfony *2.1*.
* The [1.2](https://github.com/propelorm/PropelBundle/tree/1.2) branch contains Propel *1.6+* integration for Symfony *2.2-2.3*.
* The [1.4](https://github.com/propelorm/PropelBundle/tree/1.4) branch contains Propel *1.6+* integration for Symfony *2.4-2.7*. [![Build Status](https://travis-ci.org/propelorm/PropelBundle.svg?branch=1.4)](https://travis-ci.org/propelorm/PropelBundle)

### Propel2 integration

* The [2.0](https://github.com/propelorm/PropelBundle/tree/2.0) branch contains `Propel2` integration for Symfony *2.5-2.8*. [![Build Status](https://travis-ci.org/propelorm/PropelBundle.svg?branch=2.0)](https://travis-ci.org/propelorm/PropelBundle)
* The [3.0](https://github.com/propelorm/PropelBundle/tree/3.0) branch contains `Propel2` integration for Symfony *2.8-3.x*. [![Build Status](https://travis-ci.org/propelorm/PropelBundle.svg?branch=3.0)](https://travis-ci.org/propelorm/PropelBundle)

**Note:** the `master` branch will not be updated anymore, and will trigger a `E_USER_DEPRECATED` error to notice people.

## Features

 * Generation of model classes based on an XML schema (not YAML) placed under `BundleName/Resources/*schema.xml`;
 * Insertion of SQL statements;
 * Runtime autoloading of Propel and generated classes;
 * Propel runtime initialization through the XML configuration;
 * [Propel Migrations](http://propelorm.org/Propel/documentation/10-migrations.html);
 * Reverse engineering from [existing database](http://propelorm.org/Propel/cookbook/working-with-existing-databases.html);
 * Integration to the Symfony Profiler;
 * Load SQL, YAML and XML fixtures;
 * Create/Drop databases;
 * Integration with the Form component;
 * Integration with the Security component;
 * Propel ParamConverter can be used with Sensio Framework Extra Bundle.

For documentation, see:

    Resources/doc/

[Read the documentation](https://github.com/propelorm/PropelBundle/blob/1.5/Resources/doc/index.markdown)

For license, see:

    Resources/meta/LICENSE
