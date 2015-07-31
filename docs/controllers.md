Controllers
==========

> A Controller [...] represents the classes connecting the model and the view, and is used to communicate between classes in the model and view.
>
> *Margaret Rouse (whatIs.com)*

When an URL is requested, Morrow will execute the respective _Page-Controller_ located in `modules/_main/`. In addition,
all optional _Module-Controllers_ that have been registered in `modules/modules.php` will be executed.


Naming Conventions
------------------

Controller names have to start with an uppercase Character. All other character have to be lowercase. _Default-Controllers_ must
be called `_Default.php`.


Controller Inheritance
----------------------

Controllers may extend whatever you want them to extend. However, we recommended to create a _Default-Controller_ for each module.
This way, you can have each module controller extend its corresponding parent _Default-Controller_. That way, in case there are module-specific
operations that need to be done on each request, you can just handle it inside the _Default-Controller_. A good example for this would be setting
up a class member that will be referred to in other controllers of the current module.


Controller Types
----------------

### The _Default-Controller_
Each module may have a _Default-Controller_. Its purpose is to provide a base-class to let other controllers extend from.
Following is the _Default-Controller_ of the example-module _Clock_.

**modules/Clock/_Default.php**

~~~{.php}
<?php

namespace app\modules\Clock;
use Morrow\Factory;
use Morrow\Debug;

class _Default extends Factory {
	protected $_current_time;

	public function __construct() {
		$this->_current_time = new \DateTime();

		return $this->Views_Serpent;
	}
}
?>
~~~

### The _Page-Controller_
For each page, you have to provide a _Page-Controller_ located in the _Main-Module_. These are the only mandatory controllers in Morrow.
When the _Page-Controller_ gets called, Morrow will call its public function `run()`. We recommend to let it extend the corresponding module's _Default-Controller_.
Following is the _Page-Controller_ for the page `http://localhost/morrow-framework/Home`.

**modules/_main/Home.php**

~~~{.php}
<?php

namespace app\modules\_main;
use Morrow\Factory;
use Morrow\Debug;

class Home extends _Default {
	public function run() {
		return $this->Views_Serpent;
	}
}
}?>
~~~

### The _Module-Controller_
In case you have implemented a module that should run on this page, Morrow will execute its _Module-Controller_. That will only happen if that controller has been registered in `modules/modules.php`. If this controller gets executed after the _Page-Controller_, its `run()` will get passed the current _DOM-Instance_ as an argument.
Following is a _Module-Controller_ of the example-module _Clock_.

**modules/Clock/Day.php**

~~~{.php}
<?php

namespace app\modules\Clock;
use Morrow\Factory;
use Morrow\Debug;

class Day extends _Default{
	public function run(){
		$this->Views_Serpent->setContent('day', $this->_current_time->format('D'));

		return $this->Views_Serpent;
	}
}
?>
~~~

Using classes from inside controllers
-------------------------------

Many classes are provided by default with Morrow. To use them you just have to access them as a member of the controller.

If you want to initialize a class under a different instance name or you want to pass arguments to the constructor of a class you have to use the method `prepare()` which is provided by the extending of the \Morrow\Factory class. For more documentation on this take a look at the \Morrow\Factory class.

All classes you access by a member name are loaded on demand (see [Lazy loading](http://en.wikipedia.org/wiki/Lazy_loading)). So it's possible to initialize the database class in the default controller with `prepare()` although database access is not needed at all pages.

### Example

**Simple use of the benchmark class**

~~~{.php}
<?php
namespace app\modules\Foo;
use Morrow\Factory;
use Morrow\Debug;

class Bar extends _Default {
	public function run() {
		// auto initialize and use the benchmark class
		$this->Benchmark->start('Section 1');

		sleep(1);

		$this->Benchmark->stop();
		$results = $this->Benchmark->get();
	}
}
?>
~~~

**The same example but with the use of prepare()**

~~~{.php}
<?php
namespace app\modules\Foo;
use Morrow\Factory;
use Morrow\Debug;

class Bar extends _Default {
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
