Environment information
=======================

There are some constants provided from the framework that could be useful for you:


Constant               | Path                  | Description
------------------     | ------------          |
`ROOT_PATH`            | `./`                  | The absolute path to the public folder.
`PUBLIC_PATH`          | `./public/`           | The absolute path to the public folder.
`PUBLIC_STORAGE_PATH`  | `./public/storage/`   | The absolute path to the public storage folder. Use this for temporary files that should be public accessible, e.g. thumbnails of images.
`MODULES_PATH`         | `./modules/`         | The absolute path to the App folder.
`STORAGE_PATH`         | `./storage/`          | The absolute path to the storage folder. Use this for temporary files that should NOT be public accessible, e.g. internal caches.
`VENDOR_PATH`          | `./vendor/`           | The absolute path to the vendor folder.

As in these constants all classes expect folder paths to have a trailing slash.

The Page class
--------------

The \Morrow\Page class is provided by the framework and gives you information we think could be very helpful on working with the framework.
Its output is automatically passed to the view handler.

Here is the content of the current page:

~~~
$ => Array (4)
(
    ['nodes'] => Array (2)
    (
        ['0'] = String(4) "page"
        ['1'] = String(11) "environment"
    )
    ['controller'] = String(9) "\app\Page"
    ['base_href'] = String(32) "http://192.168.1.12/morrow/docs/"
    ['path'] => Array (4)
    (
        ['relative'] = String(16) "page/environment"
        ['relative_with_query'] = String(16) "page/environment"
        ['absolute'] = String(48) "http://192.168.1.12/morrow/docs/page/environment"
        ['absolute_with_query'] = String(48) "http://192.168.1.12/morrow/docs/page/environment"
    )
)
~~~

In the controller you have access to the page array by the \Morrow\Page class.

~~~{.php}
<?php
namespace app;
use Morrow\Factory;
use Morrow\Debug;

class Foobar extends _Default {
    public function run() {
        // Dump the contents of the page class
        Debug::dump($this->Page->get());
    }
}
?>
~~~

In the templates it is always accessible by `$page`.
