The Fixtures
============

Fixtures are data you usually write to populate your database during the development, or static content
like menus, labels, ... you need by default in your database in production.

## Loading Fixtures ##

The following command is designed to load fixtures:

    > php app/console propel:fixtures:load [-d|--dir[="..."]] [--xml] [--sql] [--yml] [--connection[="..."]] [bundle]

As you can see, there are many options to allow you to easily load fixtures.

As usual, `--connection` allows to specify a connection. The `--dir` option allows to specify a directory
containing the fixtures (default is: `app/propel/fixtures/`).
Note that the `--dir` expects a relative path from the root dir (which is `app/`).

The `--xml` parameter allows you to load only XML fixtures.
The `--sql` parameter allows you to load only SQL fixtures.
The `--yml` parameter allows you to load only YAML fixtures.

You can mix `--xml`, `--yml` and `--sql` parameters to load XML, YAML and SQL fixtures at the same time.
If none of this parameter are set all files YAML, XML and SQL in the directory will be load.

You can pass a bundle name to load fixtures from it. A bundle's name starts with `@` like `@AcmeDemoBundle`.

    > php app/console propel:fixtures:load @AcmeDemoBundle


### XML Fixtures ###

A valid _XML fixtures file_ is:

``` xml
<Fixtures>
    <Object Namespace="Awesome">
        <o1 Title="My title" MyFoo="bar" />
    </Object>
    <Related Namespace="Awesome">
        <r1 ObjectId="o1" Description="Hello world !" />
    </Related>
</Fixtures>
```


### YAML Fixtures ###

A valid _YAML fixtures file_ is:

``` yaml
Awesome\Object:
     o1:
         Title: My title
         MyFoo: bar

Awesome\Related:
     r1:
         ObjectId: o1
         Description: Hello world !

Awesome\Tag:
    t1:
        name: Foo
    t2:
        name: Baz

Awesome\Post:
    p1:
        title: A Post with tags (N-N relation)
        tags: [ t1, t2 ]
```


#### Using Faker in YAML Fixtures ####

If you use [Faker](https://github.com/fzaninotto/Faker) with its [Symfony2 integration](https://github.com/willdurand/BazingaFakerBundle),
then the PropelBundle offers a facility to use the Faker generator in your YAML files:

``` yml
Acme\DemoBundle\Model\Book:
    Book1:
        name:        "Awesome Feature"
        description: <?php $faker('text', 500); ?>
```

The aim of this feature is to be able to mix real, and fake data in the same file. Fake data are interesting to quickly
add data tou your application, but most of the time you need to rely on real data. To integrate Faker in the YAML files
allows to write strong fixtures efficiently.


## Dumping data ##

You can dump data from your database into YAML fixtures file by using this command:

    > php app/console propel:fixtures:dump [--connection[="..."]]

Dumped files will be written in the fixtures directory: `app/propel/fixtures/` with the following name:
`fixtures_99999.yml` where `99999` is a timestamp.

Once done, you will be able to load these files by using the `propel:fixtures:load` command.


[Back to index](index.markdown)
