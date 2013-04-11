Configuration
=============

In order to use Propel, you have to configure few parameters in your `app/config/config.yml` file.

If you are **not** using Composer, add this configuration:

``` yaml
# in app/config/config.yml
propel:
    path:       "%kernel.root_dir%/../vendor/propel"
    phing_path: "%kernel.root_dir%/../vendor/phing"
```

Now, you can configure your application.


## Basic Configuration ##

If you have just one database connection, your configuration will look like as following:

``` yaml
# app/config/config*.yml
propel:
    dbal:
        driver:               mysql
        user:                 root
        password:             null
        dsn:                  mysql:host=localhost;dbname=test;charset=UTF8
        options:              {}
        attributes:           {}
```

The recommended way to fill in these information is to use parameters:

``` yaml
# app/config/config*.yml
propel:
    dbal:
        driver:               %database_driver%
        user:                 %database_user%
        password:             %database_password%
        dsn:                  %database_driver%:host=%database_host%;dbname=%database_name%;charset=UTF8
        options:              {}
        attributes:           {}
```


## Configure Multiple Connection ##

If you have more than one connection, or want to use a named connection, the configuration
will look like:

``` yaml
# app/config/config*.yml
propel:
    dbal:
        default_connection:         conn1
        connections:
            conn1:
                driver:             mysql
                user:               root
                password:           null
                dsn:                mysql:host=localhost;dbname=db1
            conn2:
                driver:             mysql
                user:               root
                password:           null
                dsn:                mysql:host=localhost;dbname=db2
```


## Configure Master/Slaves ##

You can also configure Master/Slaves:

``` yaml
# app/config/config*.yml
propel:
    dbal:
        default_connection:         default
        connections:
            default:
                driver:             mysql
                user:               root
                password:           null
                dsn:                mysql:host=localhost;dbname=master
                slaves:
                    slave_1:
                        user:       root
                        password:   null
                        dsn:        mysql:host=localhost;dbname=slave_1
```


## Attributes, Options, Settings ##

``` yaml
# app/config/config*.yml
propel:
    dbal:
        default_connection:         default
        connections:
            default:
                # ...
                options:
                    ATTR_PERSISTENT: false
                attributes:
                    ATTR_EMULATE_PREPARES: true
                settings:
                    charset:        { value: UTF8 }
                    queries:        { query: 'INSERT INTO BAR ('hey', 'there')' }
                model_paths:
                    - /src/Acme/DemoBundle/Model/
                    - /vendor/
```

`options`, `attributes` and `settings` are parts of the runtime configuration. See [Runtime Configuration File](http://www.propelorm.org/reference/runtime-configuration.html) documentation for more explanation.
`model_paths` can be defined to speed up searching for model data. By default it searches in the whole project from project root.

## Logging ##

You can disable the logging by changing the `logging` parameter value:

``` yaml
# in app/config/config.yml
propel:
    logging:    %kernel.debug%
```


[Back to index](index.markdown) | [Configure Propel](propel_configuration.markdown)
