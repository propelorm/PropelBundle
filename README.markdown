Propel Bundle
=============

This is a (work in progress) implementation of Propel in Symfony2.

Currently supports:

 * Generation of model classes based on an XML schema (not YAML) placed under `BundleName/Resources/*schema.xml`.
 * Insertion of SQL statements.
 * Runtime autoloading of Propel and generated classes.
 * Propel runtime initialization through the XML configuration.
 * Migrations [Propel 1.6](http://www.propelorm.org/wiki/Documentation/1.6/Migrations).
 * Reverse engineering from [existing database](http://www.propelorm.org/wiki/Documentation/1.6/Existing-Database).
 * Integration to the Symfony2 Profiler.
 * Load XML fixtures.

Installation
------------

 * Clone this bundle in the `vendor/bundles/Propel` directory:

    > git submodule add https://github.com/willdurand/PropelBundle.git vendor/bundles/Propel/PropelBundle

 * Checkout Propel and Phing in the `vendor` directory:

    > svn checkout http://svn.propelorm.org/branches/1.6 vendor/propel

    > svn checkout http://phing.mirror.svn.symfony-project.com/tags/2.3.3 vendor/phing

 * Instead of using svn, you can clone the unofficial Git repositories:

    > git submodule add https://github.com/Xosofox/phing vendor/phing

    > git submodule add https://github.com/Xosofox/propel1.6 vendor/propel

 * Register this bundle in the `AppKernel` class:
 
        public function registerBundles()
        {
            $bundles = array(
                ...

                // PropelBundle
                new Propel\PropelBundle\PropelBundle(),
                // register your bundles
                new Sensio\HelloBundle\HelloBundle(),
            );

            ...
        }

  * Don't forget to register the PropelBundle namespace in `app/autoload.php`:

        $loader->registerNamespaces(array(
            ...

            'Propel' => __DIR__.'/../vendor/bundles',
        ));


Sample Configuration
--------------------

### Project configuration

    # in app/config/config.yml
    propel:
        path:       "%kernel.root_dir%/../vendor/propel"
        phing_path: "%kernel.root_dir%/../vendor/phing"
    #    charset:   "UTF8"
    #    logging:   %kernel.debug%
    #    build_properties:
    #        xxxxx.xxxxx: xxxxxx
    #        xxxxx.xxxxx: xxxxxx

    # in app/config/config*.yml
    propel:
        dbal:
            driver:               mysql
            user:                 root
            password:             null
            dsn:                  mysql:host=localhost;dbname=test
            options:              {}
            attributes:           {}
    #        default_connection:       default
    #        connections:
    #           default:
    #               driver:               mysql
    #               user:                 root
    #               password:             null
    #               dsn:                  mysql:host=localhost;dbname=test
    #               options:              {}
    #               attributes:           {}


### Build properties

You can define _build properties_ by creating a `propel.ini` file in `app/config` and put build properties (see [Build properties Reference](http://www.propelorm.org/wiki/Documentation/1.6/BuildConfiguration)).

    # in app/config/propel.ini
    xxxx.xxxx.xxxx = XXXX

But you can follow the Symfony2 way by adding build properties in `app/config/config.yml`:

    # in app/config/config.yml
    propel:
        build_properties:
            xxxxx.xxxx.xxxxx:   XXXX
            xxxxx.xxxx.xxxxx:   XXXX
            ...


### Sample Schema

Place the following schema in `src/Sensio/HelloBundle/Resources/config/schema.xml` :

    <?xml version="1.0" encoding="UTF-8"?>
    <database name="default" namespace="Sensio\HelloBundle\Model" defaultIdMethod="native">

        <table name="book">
            <column name="id" type="integer" required="true" primaryKey="true" autoIncrement="true" />
            <column name="title" type="varchar" primaryString="1" size="100" />
            <column name="ISBN" type="varchar" size="20" />
            <column name="author_id" type="integer" />
            <foreign-key foreignTable="author">
                <reference local="author_id" foreign="id" />
            </foreign-key>
        </table>

        <table name="author">
            <column name="id" type="integer" required="true" primaryKey="true" autoIncrement="true" />
            <column name="first_name" type="varchar" size="100" />
            <column name="last_name" type="varchar" size="100" />
        </table>

    </database>


Commands
--------

### Build Process

Call the application console with the `propel:build` command:

    > php app/console propel:build [--classes] [--sql] [--insert-sql]


### Insert SQL

Call the application console with the `propel:insert-sql` command:

    > php app/console propel:insert-sql [--force]

Note that the `--force` option is needed to actually execute the SQL statements.


### Use The Model Classes

Use the Model classes as any other class in Symfony2. Just use the correct namespace, and Symfony2 will autoload them:

    class HelloController extends Controller
    {
        public function indexAction($name)
        {
            $author = new \Sensio\HelloBundle\Model\Author();
            $author->setFirstName($name);
            $author->save();

            return $this->render('HelloBundle:Hello:index.html.twig', array('name' => $name, 'author' => $author));
        }
    }


### Migrations

Generates SQL diff between the XML schemas and the current database structure:

    > php app/console propel:migration:generate-diff

Executes the migrations:

    > php app/console propel:migration:migrate

Executes the next migration up:

    > php app/console propel:migration:migrate --up

Executes the previous migration down:

    > php app/console propel:migration:migrate --down

Lists the migrations yet to be executed:

    > php app/console propel:migration:status


### Working with existing databases

Run the following command to generate an XML schema from your `default` database:

    > php app/console propel:reverse

You can define which connection to use:

    > php app/console propel:reverse --connection=default

You can dump data from your database in XML to `app/propel/dump/xml/`:

    > php app/console propel:data-dump [--connection[="..."]]

Once you ran `propel:data-dump` you can generate SQL statements from dumped data:

    > php app/console propl:data-sql

SQL will be write in `app/propel/sql/`.


### Fixtures

You can load your own fixtures by using the following command:

    > php app/console propel:load-fixtures [-d|--dir[="..."]] [--xml] [--sql] [--connection[="..."]]

As usual, `--connection` allows to specify a connection.

The `--dir` option allows to specify a directory containing the fixtures (default is: `app/propel/fixtures/`).
Note that the `--dir` expects a relative path from the root dir (which is `app/`).

The --xml parameter allows you to load only XML fixtures.
The --sql parameter allows you to load only SQL fixtures.
You can mix --xml parameter and --sql parameter to load XML and SQL fixtures.
If none of this parameter are set all files, XML and SQL, in the directory will be load.

A valid _XML fixtures file_ is:

    <?xml version="1.0" encoding="utf-8"?>
    <dataset name="all">
        <Object Id="..." />
    </dataset>


### Graphviz

You can generate **Graphviz** file for your project by using the following command line:

    > php app/console propel:graphviz

It will write files in `app/propel/graph/`.


Known Problems
--------------

Your application must not be in a path including dots in directory names (i.e. '/Users/me/symfony/2.0/sandbox/' fails).
