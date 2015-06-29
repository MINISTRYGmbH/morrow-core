Views
============

> A View [...] is a collection of classes representing the elements in the user interface (all of the things the user can see and respond to on the screen, such as buttons, display boxes, and so forth).
>
> *Margaret Rouse (whatIs.com)*

At the end you want to display data.
To display data your controller has to return a string, a stream/handle or a view handler.


Returning a string
------------------

The most simple you can do is just to return a string in your controller:

~~~{.php}
<?php
namespace app;
use Morrow\Factory;
use Morrow\Debug;

class Foobar extends _Default {
    public function run() {
        return 'Hello world!';
    }
}
?>
~~~


Returning a stream/handle
-------------------------

This is very useful if you want to output a large file.
One possibility would have been to load the file into a variable and output it as string. 
But this way you would load a big file into memory.
Think of a 1GB movie file.
That would kill your script.

So just return the file handle (or any other stream) in your controller and you will not have any problem:
 
~~~{.php}
<?php
namespace app;
use Morrow\Factory;
use Morrow\Debug;

class Foobar extends _Default {
    public function run() {
        return fopen('big_file.mp4', 'r');
    }
}
?>
~~~


Returning a view handler
-------------------------

This is the usually used way to display data.
Every view handler has to extend \Morrow\Views\AbstractView to be accepted as valid return value.
Morrow provides several view handlers to output your data in different ways: HTML, XML, CSV, JSON etc.

~~~{.php}
<?php
namespace app;
use Morrow\Factory;
use Morrow\Debug;

class Foobar extends _Default {
    public function run() {
        $view = Factory::load('Views\Xml');
        $view->setContent('content', $_SERVER);
        return $view;
    }
}
?>
~~~

Oh, you want to get the results as JSON data? No problem: Just change the handler class to JSON:

~~~{.php}
        $view = Factory::load('Views\Json');
~~~

For a more detailed description of the view handlers take a look at the corresponding documentation page.

