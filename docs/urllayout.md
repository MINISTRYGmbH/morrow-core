URL Layout
==========

We think the URL is an important hierarchical navigation element for the user of a website.
It is like a breadcrumb (also for search engines like Google) and that we decided to design our URL layout.
So by default Morrow does not have the typical *application layout* known from other frameworks:  

**URL:** `http://example.com/product/show/cool-funky-product`  

Our URLs have a hierarchical character and can be unlimited levels deep.  

**URL:** `http://example.com/products/categories/cool-funky-product`  

### Write your URL paths without a trailing slash

At [Multiple sites](page/multiplesites) you will get an .htaccess file solution to handly multiple sites simply. You can e.g. have a project `docs` and a page `docs` in your main project.
To differentiate them the trailing slash is used.

The URL `http://example.com/docs/` would request the project `docs` whereas `http://example.com/docs` would request the page `docs` in the main project.
If there is no project name which could lead to confusion you could write the trailing slash without any problem. But to avoid the possibility of confusion you should omit it.


Controller mapping
-------------------

In other frameworks you can set routes that define which URL should map to which controller.
But in Morrow you don't have to. In `configs/_default.php` we set a fallback routine which does some type of auto mapping.
We will explain later how you can modify this.
But at the moment it is just important that there is a fallback routine defined which behaves like this:

Morrow takes the given URL path (e.g. `products/categories/cool-funky-product`), changes it to lower case (the first letter to uppercase), replaces slashes by underscores,
removes all characters that are not valid in a PHP class name (valid are `0-9`, `a-z` and `_`) and sets the namespace `\app\` as prefix.

**URL:** `http://example.com/products/categories/cool-funky-product`  
becomes to  
**Controller:** `app\Products_categories_coolfunkyproduct`

Composers autoloader will now try to load the file `app/Products_categories_coolfunkyproduct.php` and initializes the class `Products_categories_coolfunkyproduct` within the namespace `app\`.

### Keep in mind

 * Because slashes are converted to underscores you shouldn't use underscores in your URL to prevent ambiguities.
 * With the predefined fallback routine URL paths are by default case insensitive. `products/categories/cool-funky-product/` loads the same controller as `Products/Categories/Cool-Funky-Product`.
So you have to take care of using the same notation website wide because search engines respect different notations and could rate your pages as duplicate content.


Template mapping
----------------

In our experience it is the most common case to have one template per controller with a hierarchical URL layout.
This is the reason why in Morrow you don't have to specify the template you want to render in your controller.
The generation of the file name for the template is predefined by a rule in `configs/_default.php`.
It is derived by the controller name and behaves like this:

It takes the controllers full path (e.g. `app\Products_categories_coolfunkyproduct`) and removes the namespace if its name is `app\`.
So the fliename of the template will become

**Template:** `app/templates/Products_categories_coolfunkyproduct.htm`


Defining own routes
-------------------

Own routes are a nice thing if you want to set different controllers as the framework would normally use.
This is very useful if you want to build speaking or just clean URLs.

The following are the routes we use for this documentation which itself is a Morrow project.

**app/configs/_default_app.php**
~~~{.php}
...
// routing rules
    'router.routes'                 => array(
        // '=^$='                   => '\app\Home',
        '=^object/(?P<path>.+)$='   => '\app\Object',
        '=^page/(?P<id>.+)$='       => '\app\Page',
        '=^feature/(?P<name>.+)$='  => '\app\Feature',
    ),
    'router.fallback'               =>  function($url) { return '\app\Error404'; },
...
~~~

Each array entry in `router.routes` defines a route.
The key is a simple regular expression. So you are able to evaluate the path as you like. It has to match the incoming URL path.
The value defines the name of the controller.

In the routes above we commented out a route with an empty key.
This is usually the only defined route and says: "If there is no URL path given, load the controller `\app\Home`".
You would change that if you want to use a different default URL path.

But we have also changed the fallback routine.
With this routine we have said: "If the given route is unknown, use the controller `\app\Error404`" (which showns an Error page).
That is the reason why we don't need the empty key anymore.
Use this if you want to explicitely only allow defined routes.

Another possiblity have been to define the fallback this way:

~~~{.php}
    'router.fallback'               =>  function($url) { \Morrow\Factory::load('Url')->redirect('page/introduction'); },
~~~

This way you would have been redirected to a page of your choice if a route was unknown.


Dynamic routes
--------------

The key of a route is a simple regular expression.
So you are able to evaluate the path as you like.

Imagine you want to have URL paths like `products/category/this-is-the-product-2067` rather than `products/?id=2067`.
Just do it like this:

**app/configs/_default_app.php**
~~~{.php}
...
// routing rules
    'router.routes' = array(
        '=^$=' => '\app\Home',
        '=^products/(?P<category>.+)/.+-(?P<product_id>\d+)$=' => '\app\Products',
    ),
...
~~~

In this example the controller `app/Products.php` will be used and you have access to the category and the product via `$this->input->get('routed.category')` and `$this->input->get('routed.product_id')`.
We have used named groups (a feature of regular expressions) to name the parameters. You could also have used

**app/configs/_default_app.php**
~~~{.php}
...
// routing rules
    'router.routes' = array(
        '=^$=' => '\app\Home',
        '=^products/(.+)/.+-(\d+)$=' => '\app\Products',
    ),
...
~~~

Then you would have had the parameters available via `$this->input->get('routed.1')` for the category and `$this->input->get('routed.2')` for the product id.


Change URL layout to the application layout
--------------------------

If you are writing an application rather than creating a presentational website, it can make more sense to use the *application layout*.
Just define the following route and call the action in your default controller manually.

**app/configs/\_default\_app.php**
~~~{.php}
    'router.routes' = array(
        '(?P<controller>[^/]+)/(?P<action>[^/]+)(?P<params>/.*)?'   => '\app\$1'
    ),
);
~~~

**app/\_Default.php**
~~~{.php}
// init "application url design"
$controller = $this->input->get('routed.controller');
$action     = $this->input->get('routed.action');
$params     = explode('/', trim($this->input->get('routed.params'), '/'));

if (!is_null($action)) {
    if (!method_exists($this, $action)) {
        $this->url->redirect( $this->page->get('base_href') );
    }
    call_user_func_array( array($this, $action), $params);

    // set default template
    $this->view->template = $controller . '_' . $action;
}
~~~
