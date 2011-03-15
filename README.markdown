Propel Bundle
=============

This is a (work in progress) implementation of Propel in Symfony2.

Currently supports:

 * Generation of model classes based on an XML schema (not YAML) placed under `BundleName/Resources/*schema.xml`.
 * Insertion of SQL statements.
 * Runtime autoloading of Propel and generated classes.
 * Propel runtime initialization through the XML configuration.
 * Migrations [Propel 1.6](http://www.propelorm.org/wiki/Documentation/1.6/Migrations).


Installation
------------

 * Clone this bundle in the `src/Propel` directory:

    > git submodule add https://github.com/willdurand/PropelBundle.git src/Propel/PropelBundle

 * Checkout Propel and Phing in the `vendor` directory:

    > cd vendor

    > svn checkout http://svn.propelorm.org/branches/1.6 propel

    > svn checkout http://phing.mirror.svn.symfony-project.com/tags/2.3.3 phing

 * Instead of using svn, you can clone the unofficial Git repositories:

    > cd vendor

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

            'Propel' => __DIR__.'/../src',
        ));


Sample Configuration
--------------------

### Project configuration

    # in app/config/config.yml
    propel:
        path:       "%kernel.root_dir%/../vendor/propel"
        phing_path: "%kernel.root_dir%/../vendor/phing"
    #    charset:   "UTF8"

    # in app/config/config*.yml
    propel:
        dbal:
            driver:               mysql
            user:                 root
            password:             null
            dsn:                  mysql:host=localhost;dbname=test
            options:              {}
    #        default_connection:       default
    #        connections:
    #           default:
    #               driver:               mysql
    #               user:                 root
    #               password:             null
    #               dsn:                  mysql:host=localhost;dbname=test
    #               options:              {}


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

    > php app/console propel:build [--classes] [--sql]


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


Known Problems
--------------

Your application must not be in a path including dots in directory names (i.e. '/Users/me/symfony/2.0/sandbox/' fails).
