The Commands
============

The PropelBundle provides a lot of commands to manage migrations, database/table manipulations,
and so on.


## Database Manipulations ##

You can create a **database**:

    > php bin/console propel:database:create [--connection[=""]]

As usual, `--connection` allows to specify a connection.


You can drop a **database**:

    > php bin/console propel:database:drop [--connection[=""]] [--force]

As usual, `--connection` allows to specify a connection.

Note that the `--force` option is needed to actually execute the SQL statements.


## Form Types ##

You can generate stub classes based on your `schema.xml` in a given bundle:

    > php bin/console propel:form:generate [-f|--force] bundle [models1] ... [modelsN]

It will write Form Type classes in `src/YourVendor/YourBundle/Form/Type`.

You can choose which Form Type to build by specifing Model names:

    > php bin/console propel:form:generate @AcmeDemoBundle Book Author


## Graphviz ##

You can generate **Graphviz** file for your project by using the following command line:

    > php bin/console propel:graphviz:generate

It will write files in `app/propel/graph/`.


## Migrations ##

Generates SQL diff between the XML schemas and the current database structure:

    > php bin/console propel:migration:generate-diff [--connection[=""]]

As usual, `--connection` allows to specify a connection.

Executes the migrations:

    > php bin/console propel:migration:migrate

Executes the next migration up:

    > php bin/console propel:migration:migrate --up

Executes the previous migration down:

    > php bin/console propel:migration:migrate --down

Lists the migrations yet to be executed:

    > php bin/console propel:migration:status


## Table Manipulations ##

You can drop one or several **tables**:

    > php bin/console propel:table:drop [--force] [--connection[="..."]] [table1] ... [tableN]

As usual, `--connection` allows to specify a connection.

The table arguments define which table will be delete, by default all table.

Note that the `--force` option is needed to actually execute the deletion.


## Working with existing databases ##

Run the following command to generate an XML schema from your `default` database:

    > php bin/console propel:reverse

You can define which connection to use:

    > php bin/console propel:reverse --connection=default


[Back to index](index.markdown)
