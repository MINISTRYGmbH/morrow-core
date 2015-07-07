URL Layout
==========

We think the URL is an important hierarchical navigation element for the user of a website.
It is like a breadcrumb (also for search engines like Google) and that we decided to design our URL layout.
So by default Morrow does not have the typical *application layout* known from other frameworks:

**URL:** `http://example.com/product/show/cool-funky-product`

Our URLs have a hierarchical character and can be unlimited levels deep.

**URL:** `http://example.com/products/categories/cool-funky-product`

### Write your URL paths without a trailing slash

At [Multiple Projects](page/multiplesites) you will get an .htaccess file solution to handle multiple projects simply. You can e.g. have a project `docs` and a page `docs` in your docs project main module.
To differentiate them the trailing slash is used.

The URL `http://example.com/docs/` would request the project `docs` whereas `http://example.com/docs` would request the page `docs` in the main module.
If there is no project name which could lead to confusion you could write the trailing slash without any problem. But to avoid the possibility of confusion you should omit it.

### Allowed path characters

Allowed characters in the path are `0-9`, `a-z` and `_`. Files with dots (e.g. assets like images or CSS/JS files) are not processed by Morrow.


Controller mapping
-------------------

The default controller mapping definition is located in the main config file `configs/_default.php`. You can find it under the key `router.fallback`.
It is a function which gets passed the url relative to the project root and is expected to return the controller namespace. When requesting `http://localhost/morrow-framework/Hello/World`, it gets passed `Hello/World`.

Following steps will be performed:

 * replace all `_` with `/`
 * transform the whole string to lowercase
 * transform the first letter to uppercase
 * remove all characters that are not alphanumeric or `_`
 * put `\app\modules\_main\` in front of it
 * return the resulting namespace string

So, for example, `http://localhost/morrow-framework/Hello/World` would result in `\app\modules\_main\\Hello_world`.

Composers autoloader will now try to load the file `modules/_main/Hello_world.php` and initializes the class `Hello_world` within the namespace `app\modules\_main\\Hello_world`.

### Keep in mind

 * Because slashes are converted to underscores you shouldn't use underscores in your URL to prevent ambiguities.
 * With the predefined fallback routine URL paths are by default case insensitive.
So you have to take care of using the same notation website wide because search engines respect different notations and could rate your pages as duplicate content.


Template mapping
----------------

In our experience it is the most common case to have one template per controller with a hierarchical URL layout.
That is why Morrow doesn't expect you to specify the template path inside of the controller.
According to the controller mapping mechanoism, the default template mapping definition is also located in the main config file `configs/_default.php`. You can find that one with the key `router.template`. It is a function which gets passed the controller namespace and is expected to return the template file name.

Following steps will be performed:

 * trim all `\`
 * remove first 3 parts of the namespace
 * return the resulting template name string

So, for example, `http://localhost/morrow-framework/Hello/World` would result in `Hello_world`.


Defining custom routes
-------------------

Custom routes are a nice thing if you want to set different controllers as the framework would normally use.
This is very useful if you want to build speaking or just clean URLs.

Each array entry in `router.routes` defines a route.
The key is a simple regular expression to provide much flexibility, has to match the incoming URL path relative to the project route.
The value defines the name of the controller.

The default route `'=^$=' => '\\app\\modules\\_main\\\Home'`, for example, defines the controller that is called when the received URL path relative to the project root is empty.
So basically this line works as this: _"If there is no URL path given, load the controller `\app\\Home`"._


Dynamic routes
--------------

Imagine you want to have URL paths like `products/category/this-is-the-product-2067` rather than `products/?id=2067`.
Just do it like this:

**configs/_default_app.php**
~~~{.php}
...
// routing rules
    'router.routes' = [
        '=^$=' => '\\app\\modules\\_main\\\Home',
        '=^products/(?P<category>.+)/.+-(?P<product_id>\d+)$=' => '\\app\\modules\\_main\\\Products',
    ],
...
~~~

In this example the controller `modules/_main/Products.php` will be used. To access these values, use the Input class like this: `$category = $this->Input->get('routed.category');` and `$product_id = $this->Input->get('routed.product_id');`.
We have used named groups (a feature of regular expressions) to name the parameters. You could also have used

**configs/_default_app.php**
~~~{.php}
...
// routing rules
    'router.routes' = [
        '=^$=' => '\\app\\modules\\_main\\\Home',
        '=^products/(.+)/.+-(\d+)$=' => '\\app\\modules\\_main\\\Products',
    ],
...
~~~

Then you would have had the parameters available via `$this->input->get('routed.1')` for the category and `$this->input->get('routed.2')` for the product id.


Change URL layout to the application layout
--------------------------

If you are writing an application rather than creating a presentational website, it can make more sense to use the *application layout*.
Just define the following route and call the action in your default controller manually.

**configs/_default_app.php**
~~~{.php}
...
// routing rules
    'router.routes' = [
        '(?P<controller>[^/]+)/(?P<action>[^/]+)(?P<params>/.*)?'   => '\\app\\modules\\_main\\$1'
    ],
...
~~~

**modules/_main/\_Default.php**
~~~{.php}
// init "application url design"
$controller = $this->Input->get('routed.controller');
$action     = $this->Input->get('routed.action');
$params     = explode('/', trim($this->Input->get('routed.params'), '/'));

if (!is_null($action)) {
    if (!method_exists($this, $action)) {
        $this->Url->redirect( $this->Page->get('base_href') );
    }
    call_user_func_array( [$this, $action], $params);

    // set default template
    $this->Views_Serpent->template = $controller . '_' . $action;
}
~~~
