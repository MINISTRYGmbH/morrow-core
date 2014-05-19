Controllers
==========

The controller is the central point where you do all your work.
In the previous article you have seen how the alias is created and how the controller path is derived from it.

All controllers are located in the folder `App/`. So Morrow will now use the controller file:

`App/products_hard-stuff_funky-stuff_cool-funky-product.class.php`

The controller inheritance
---------------------------

The principle is very simple. A page specific controller (PageController) extends a site wide controller (DefaultController).

### The DefaultController

This default controller is loaded if your PageController extends the DefaultController, and is under full control of the user. The file has to be called `App/_default.php` and there has to be a method `setup()` which is automatically called. So your origin DefaultController looks like that:

**App/_default.php**

~~~{.php}
<?php
namespace App;
use Morrow\Factory;
use Morrow\Debug;

class DefaultController extends Factory {
	public function setup() {
		// set a default view handler
		$this->view->setHandler('serpent');
	}
}
?>
~~~

### The PageController

At least your URL specific controller gets loaded. It extends the DefaultController and has to contain a method `run()` which is automatically called. It looks like this:

**App/products_hard-stuff_funky-stuff_cool-funky-product.php**

~~~{.php}
<?php
namespace App;
use Morrow\Factory;
use Morrow\Debug;

class PageController extends DefaultController {
	public function run() {
	}
}
?>
~~~

Using classes in the controllers
-------------------------------

Many classes are provided per default with Morrow. To use them you just have to access them as a member of the controller.

If you want to initialize a class under a different instance name or you want to pass arguments to the constructor of a class you have to use the method `prepare()` which is provided by the extending of the \Morrow\Factory class. For more documentation on this take a look at the \Morrow\Factory class.

All classes you access by a member name are loaded on demand (see Lazy loading). So it's possible to init the database class in the GlobalController with `prepare()` although database access is not needed in all pages.

### Example

**Simple use of the benchmark class**

~~~{.php}
<?php
namespace App;
use Morrow\Factory;
use Morrow\Debug;

class PageController extends DefaultController {
	public function run() {
		// auto initialize and use the benchmark class
		$this->benchmark->start('Section 1');
		
		sleep(1);
			   
		$this->benchmark->stop();
		$results = $this->benchmark->get();
	}
}
?>
~~~

**The same example but with the use of prepare()**

~~~{.php}
<?php
namespace App;
use Morrow\Factory;
use Morrow\Debug;

class PageController extends DefaultController {
	public function run() {
		// load the benchmark class under a different instance name
		$this->prepare('benchmark:bm');
	   
		// auto initialize and use the benchmark class
		$this->bm->start('Section 1');
			   
		sleep(1);
			   
		$this->bm->stop();
		$results = $this->bm->get();
	}
}
?>
~~~
