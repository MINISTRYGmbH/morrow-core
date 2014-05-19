Views
============

At the end you want to display data. Morrow provides many so called view handlers which let you output your data in different ways: (X)HTML, XML, CSV and some more.
Your output is handled via the \Morrow\View class which is automatically loaded.

Example
-------

~~~{.php}
<?php
namespace App;
use Morrow\Factory;
use Morrow\Debug;

class PageController extends DefaultController {
    public function run() {
        // auto initialize and use the benchmark class
        $this->load('benchmark:bm');
       
        // use the benchmark class
        $this->bm->start('Section 1');
               
        sleep(1);
               
        $this->bm->stop();
        $results = $this->bm->get();
               
        // output the results
        $this->view->setHandler('xml');
        $this->view->setContent('content', $results);
    }
}
?>
~~~

As you can see, the only thing you have to do is define the handler which should be used for the output, and pass the data you want to display to the view class.

Oh, you want to get the results as JSON data? No problem: Just change the handler to JSON

~~~{.php}
$this->view->setHandler('json');
~~~

and you are done.

Take a look at the other view handlers to see what is possible.


View filters
-------------

Filters are functions called after the content generation to modify the output.
You could build a page spider (there is one integrated in Morrow called \Morrow\Sitesearch), you could uppercase a company name on all pages with a regular expression and so on.

The following example for example replaces all occurences of `#TIME#` with an actual unix timestamp.

~~~{.php} 
<?php
namespace App;
use Morrow\Factory;
use Morrow\Debug;

class PageController extends DefaultController {
    public function run() {
        $this->view->setFilter('generic', array('str_replace', '#TIME#', time(), ':CONTENT') );
 
    }
}
?>
~~~
