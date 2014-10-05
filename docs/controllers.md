Controllers
==========

The controller is the central point where you do all your work.
In the previous article you have seen how the alias is created and how the controller path is derived from it.

All controllers are located in the folder `app/` and are the only files there.

The controller inheritance
---------------------------

The principle is very simple. A page specific controller extends an optional site wide default controller.

### The default controller

This default controller is loaded if your page specific controller extends the default controller, and is under full control of the user. 
The file should be called `app/_Default.php` by convention.

**app/_Default.php**

~~~{.php}
<?php
namespace app;
use Morrow\Factory;
use Morrow\Debug;

class _Default extends Factory {
	public function __construct() {
		// define actions you want to execute on every request
		// ...
	}
}
?>
~~~

### The page specific controller

At least your URL specific controller gets loaded. It extends the default controller and has to contain a method `run()` which is automatically called. It looks like this:

**app/Products_coolfunkyproduct.php**

~~~{.php}
<?php
namespace app;
use Morrow\Factory;
use Morrow\Debug;

class Products_coolfunkyproduct extends _Default {
	public function run() {
	}
}
?>
~~~

Using classes in the controllers
-------------------------------

Many classes are provided by default with Morrow. To use them you just have to access them as a member of the controller.

If you want to initialize a class under a different instance name or you want to pass arguments to the constructor of a class you have to use the method `prepare()` which is provided by the extending of the \Morrow\Factory class. For more documentation on this take a look at the \Morrow\Factory class.

All classes you access by a member name are loaded on demand (see [Lazy loading](http://en.wikipedia.org/wiki/Lazy_loading)). So it's possible to initialize the database class in the default controller with `prepare()` although database access is not needed at all pages.

### Example

**Simple use of the benchmark class**

~~~{.php}
<?php
namespace app;
use Morrow\Factory;
use Morrow\Debug;

class Products_hardstuff_funkystuff_coolfunkyproduct extends _Default {
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
namespace app;
use Morrow\Factory;
use Morrow\Debug;

class Products_hardstuff_funkystuff_coolfunkyproduct extends _Default {
	public function run() {
		// load the benchmark class under a different instance name
		$this->prepare('Benchmark:bm');
	   
		// auto initialize and use the benchmark class
		$this->bm->start('Section 1');
			   
		sleep(1);
			   
		$this->bm->stop();
		$results = $this->bm->get();
	}
}
?>
~~~
