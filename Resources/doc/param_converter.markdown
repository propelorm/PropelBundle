The PropelParamConverter
========================

You can use the `PropelParamConverter` with the [SensioFrameworkExtraBundle](http://github.com/sensio/SensioFrameworkExtraBundle).
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

**New** with last version of `SensioFrameworkExtraBundle`,
you can ommit the `class` parameter if your controller parameter is typed,
this is usefull when you need to set extra `options`.

``` php
<?php
use BlogBundle\Model\Post;

/**
 * @ParamConverter("post")
 */
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

#### Hydrate related object ####

You could hydrate related object with the "with" option:

``` php
<?php

/**
 * @ParamConverter("post", class="BlogBundle\Model\Post", options={"with"={"Comments"}})
 */
public function myAction(Post $post)
{
}
```

You can set multiple with ```"with"={"Comments", "Author", "RelatedPosts"}```.

The default join is an "inner join" but you can configure it to be a left join, right join or inner join :

``` php
<?php

/**
 * @ParamConverter("post", class="BlogBundle\Model\Post", options={"with"={ {"Comments", "left join" } }})
 */
public function myAction(Post $post)
{
}
```
Accepted parameters for join :

* left, LEFT, left join, LEFT JOIN, left_join, LEFT_JOIN
* right, RIGHT, right join, RIGHT JOIN, right_join, RIGHT_JOIN
* inner, INNER, inner join, INNER JOIN, inner_join, INNER_JOIN

#### Named converter ####

If you have a conflict with another ParamConverter you can force the `PropelParamConverter` with the `converter` option.

 ``` php
 <?php

 /**
  * @ParamConverter("post", converter="propel")
  */
 public function myAction(Post $post)
 {
 }
 ```

[Back to index](index.markdown)
