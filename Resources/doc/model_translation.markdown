ModelTranslation
================

The `PropelBundle` provides a model-based implementation of the Translation components' loader and dumper.
To make us of this `ModelTranslation` you only need to add the translation resource.

``` yaml
services:
    translation.loader.propel:
        class: Propel\PropelBundle\Translation\ModelTranslation
        arguments:
            # The model to be used.
            - 'Acme\Model\Translation\Translation'
            # The column mappings to interact with the model.
            -
                columns:
                    key: 'key'
                    translation: 'translation'
                    locale: 'locale'
                    domain: 'domain'
                    updated_at: 'updated_at'
        calls:
            - [ 'registerResources', [ '@translator' ] ]
        tags:
            - { name: 'translation.loader', alias: 'propel' }
            # The dumper tag is optional.
            - { name: 'translation.dumper', alias: 'propel' }
```

This will add another resource to the translator to be scanned for translations.

## Translation model

An example model schema for the translation model:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<database name="translation" defaultIdMethod="native" namespace="Propel\PropelBundle\Tests\Fixtures\Model">
    <table name="translation">
        <column name="id" type="integer" autoIncrement="true" primaryKey="true" />
        <column name="key" type="varchar" size="255" required="true" primaryString="true" />
        <column name="translation"  type="longvarchar" lazyLoad="true" required="true" />
        <column name="locale" type="varchar" size="255" required="true" />
        <column name="domain" type="varchar" size="255" required="true" />
        <column name="updated_at" type="timestamp" />

        <index>
            <index-column name="domain" />
        </index>
        <index>
            <index-column name="locale" />
            <index-column name="domain" />
        </index>

        <unique>
            <unique-column name="key" />
            <unique-column name="locale" />
            <unique-column name="domain" />
        </unique>
    </table>
</database>
```

### VersionableBehavior

In order to make use of the `VersionableBehavior` (or similar), you can map the `updated_at` column to the `version_created_at` column:

``` yaml
services:
    translation.loader.propel:
        class: Propel\PropelBundle\Translation\ModelTranslation
        arguments:
            - 'Acme\Model\Translation\Translation'
            -
                columns:
                    updated_at: 'version_created_at'
        calls:
            - [ 'registerResources', [ '@translator' ] ]
        tags:
            - { name: 'translation.loader', alias: 'propel' }
```

[Back to index](index.markdown)
