Propel Configuration
====================

Add a `app/config/propel.ini` file in your project, and copy the following configuration:

``` ini
# Enable full use of the DateTime class.
# Setting this to true means that getter methods for date/time/timestamp
# columns will return a DateTime object when the default format is empty.
propel.useDateTimeClass = true

# Specify a custom DateTime subclass that you wish to have Propel use
# for temporal values.
propel.dateTimeClass = DateTime

# These are the default formats that will be used when fetching values from
# temporal columns in Propel. You can always specify these when calling the
# methods directly, but for methods like getByName() it is nice to change
# the defaults.
# To have these methods return DateTime objects instead, you should set these
# to empty values
propel.defaultTimeStampFormat =
propel.defaultTimeFormat =
propel.defaultDateFormat =

# A better Pluralizer
propel.builder.pluralizer.class = builder.util.StandardEnglishPluralizer

# MySQL config
# propel.mysql.tableType = InnoDB

# Behaviors come below
```

See the [Build properties Reference](http://www.propelorm.org/reference/buildtime-configuration.html) to get more
information.


## Build properties ##

You can define _build properties_ by creating a `propel.ini` file in `app/config` like below, but you can also follow
the Symfony2 convention by adding build properties in `app/config/config.yml`:

``` yaml
# app/config/config.yml
propel:
    build_properties:
        xxxxx.xxxx.xxxxx:   XXXX
        xxxxx.xxxx.xxxxx:   XXXX
        // ...
```


[Configure the bundle](configuration.markdown) | [Back to index](index.markdown)
