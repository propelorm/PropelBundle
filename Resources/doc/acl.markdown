ACL Implementation
==================

The `PropelBundle` provides a model-based implementation of the Security components' interfaces.
To make us of this `AuditableAclProvider` you only need to change your security configuration.

``` yaml
security:
    acl:
        provider: propel.security.acl.provider
```

This will switch the provider to be the `AuditableAclProvider` of the `PropelBundle`.

The auditing of this provider is set to a sensible default. It will audit all ACL failures but no success by default.
If you also want to audit successful authorizations, you need to update the auditing of the given ACL accordingly.

After adding the provider, you only need to run the `propel:acl:init` command in order to get the model generated.
If you already got an ACL database, the schema of the `PropelBundle` is compatible with the default schema of Symfony2.

#### Separate database connection for ACL ####

In case you want to use a different database for your ACL than your business model, you only need to configure this service.

``` yaml
services:
    propel.security.acl.connection:
        class: PropelPDO
        factory_class: Propel
        factory_method: getConnection
        arguments:
            - "acl"
```

The `PropelBundle` looks for this service, and if given uses the provided connection for all ACL related operations.
The given argument (`acl` in the example) is the name of the connection to use, as defined in your runtime configuration.


[Back to index](index.markdown)
