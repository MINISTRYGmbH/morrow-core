URL Layout
==========

We think the URL is an important hierarchical navigation element for the user of a website.
It is like a breadcrumb (also for search engines like Google) and that we decided to design our URL layout.

Here an example:

**URL:** `http://example.com/products/hard-stuff/funky-stuff/cool-funky-product`

MorrowTwo takes the given URL and creates an internal identifier (`alias`).
It is the same as the URL path but with underscores instead of slashes. So the URL above will get the following alias:

**Alias:** `products_hard-stuff_funky-stuff_cool-funky-product`

The framework will now try to load and execute the following controller

**Controller:** `app/products_hard-stuff_funky-stuff_cool-funky-product.php`

and to use the template (if you have set Serpent as your default view handler)

**Template:** `app/templates/products_hard-stuff_funky-stuff_cool-funky-product.htm`


### URL nodes are case insensitive

To create the MorrowTwo alias they get lowered.
`products/cool-funky-product/` loads the same controller as `Products/Cool-Funky-Product`.
So you have to take care of using the same notation website wide because search engines respect different notations and could find duplicate content.


### Write your URLs without the trailing slash

The Morrow skeleton is able to handle multiple projects. So you can have a project `docs` and a page `docs` in your main project.
To differentiate them the trailing slash is used.

The URL `http://example.com/docs/` would request the project `docs` whereas `http://example.com/docs` would request the page `docs` in the main project.
If there is no project name which could lead to confusion you could write the trailing slash without any problem. But to avoid the possibility of confusion you should omit it.


For advanced users
------------------

If you are writing an application rather than creating a presentational website, it can make more sense to use the Controller-Action URL layout.
Just use URL Routing and call the action in your default controller by hand.

**_App/configs/\_default\_app.php**
~~~{.php}
	'routing' = array(
		'(?P<controller>[^/]+)/(?P<action>[^/]+)(?P<params>/.*)?'	=> '$1'
	),
);
~~~

**App/\_default.php**
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
    $this->view->setProperty('template', $controller . '_' . $action, 'serpent');
}
~~~
