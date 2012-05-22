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

#### Custom mapping ####

You can map route parameters directly to model column to be use for filtering.

If you have a route like `/my-route/{postUniqueName}/{AuthorId}`
Mapping option overwrite any other automatic mapping.

``` php
<?php

/**
 * @ParamConverter("post", class="BlogBundle\Model\Post", options={"mapping"={"postUniqueName":"name"}})
 * @ParamConverter("author", class="BlogBundle\Model\Author", options={"mapping"={"AuthorId":"id"}})
 */
public function myAction(Post $post, $author)
{
}
```

[Back to index](index.markdown)
