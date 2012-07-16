Propel Configuration
====================

You can add a `app/config/propel.ini` file in your project to specify some
configuration parameters. See the [Build properties Reference](
http://www.propelorm.org/reference/buildtime-configuration.html) to get more
information. However, **the recommended way** to configure Propel is to rely
on **build properties**, see the section below.

By default the PropelBundle is configured with the default parameters:

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
```


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


## Behaviors ##

You can register Propel behaviors using the following syntax:

``` yaml
# app/config/config.yml
propel:
    behaviors:
        behavior_name: My\Bundle\Behavior\BehaviorClassName
```

If you rely on third party behaviors, most of them are autoloaded so you don't
need to register them. But, for your own behaviors, you can either configure the
autoloader to autoload them, or register them in this section (this is the
recommended way when you namespace your behaviors).


[Configure the bundle](configuration.markdown) | [Back to index](index.markdown) | [Write an XML Schema](schema.markdown)
