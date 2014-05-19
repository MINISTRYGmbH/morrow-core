URL Routing
=============================

URL Routing is a nice thing if you want to internally redirect aliases to other aliases as the framework would normally use.
This is very useful if you want to build speaking or just clean URLs.

Configuration
--------------

**App/_default.php**
~~~{.php}
...
// routing rules
	'routing' = array(
	    '' => 'home/',
	),
...
~~~

Each array entry defines a rule will be run through when an URL is requested. The key is the regular expression pattern that have to match the incoming url. The value defines the target of the redirection.

As you can see in the code snippet above, there is already one rule defined. This rule defines that the alias `home` is called when there is no path given. Change that if you want to use a different default URL path.

**Do not forget to define the rules for each language!**

Usage
------

The pattern is a simple regular expression without `^` at the beginning and `$` at the end.
So you are able to evaluate the path as you like.

Example
--------

Imagine you want to have URL paths like `products/category/this-is-the-product-2067` rather than `products/?id=2067`

~~~{.php}
...
// routing rules
	'routing' = array(
		'' => 'home',
		'products/(?P<category>.+)/.+-(?P<product_id>\d+)' => 'products',
	),
...
~~~

In this example the controller `App/products.php` will be used and you have access to the category and the product via `$this->input->get('routed.category')` and `$this->input->get('routed.product_id')`.

In the first example we have used named groups (a feature of regular expressions) to name the parameters. You could also have used

~~~{.php}
...
// routing rules
	'routing' = array(
		'' => 'home',
		'products/(.+)/.+-(\d+)' => 'products',
	),
...
~~~

Then you would have had the parameters available via `$this->input->get('routed.1')` for the category and `$this->input->get('routed.2')` for the product id.