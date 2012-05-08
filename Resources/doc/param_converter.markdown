The PropelParamConverter
========================

You can use the PropelParamConverter with the [SensioFrameworkExtraBundle](http://github.com/sensio/SensioFrameworkExtraBundle).
You just need to put the right _Annotation_ on top of your controller:

``` php
<?php

/**
 * @ParamConverter("post", class="BlogBundle\Model\Post")
 */
public function myAction(Post $post)
{
}
```

Your request needs to have an `id` parameter or any field as parameter (slug, title, ...).

The _Annotation_ is optional if your parameter is typed you could only have this:

``` php
<?php

public function myAction(Post $post)
{
}
```

#### Exclude some parameters ####

You can exclude some attributes from being used by the converter:

If you have a route like `/my-route/{slug}/{name}/edit/{id}`
you can exclude `name` and `slug` by setting the option "exclude":

``` php
<?php

/**
 * @ParamConverter("post", class="BlogBundle\Model\Post", options={"exclude"={"name", "slug"}})
 */
public function myAction(Post $post)
{
}
```


[Back to index](index.markdown)
