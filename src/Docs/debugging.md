Debugging
=============================

Debugging is of course one of the most interesting topics while developing. Morrow gives you a simple and clean way to debug your applications.

Dumping variables
-----------------

The most interesting tool is Morrow's system wide replacement for print_r() and var_dump(). It returns a nice layout with a lot more of information than other tools. For example where you did the call. Never forget anymore where you have placed your debugging call. Just try out.

~~~{.php}
<?php
namespace App;
use Morrow\Factory;
use Morrow\Debug;

class PageController extends DefaultController {
	public function run() {
		Debug::dump($_SERVER);
	}
}
?>
~~~

That is the reason why `use \Morrow\Debug;` was inserted at the top of the file.
Otherwise you would have to call

~~~{.php}
\Morrow\Debug::dump($_SERVER);
~~~


Errors & Exceptions
-------------------

Morrow's preferred way is to work with exceptions. For that reason errors throw an exception, so you can catch them as you would do with normal exceptions. Furthermore we integrated a state-of-the-art-top-level-exception-handler&trade;.

~~~{.php}
// ... controller code
 
try {
    echo $undefined; // yes, the variable undefined was not defined before
} catch (Exception $e) {
    die($e->getMessage());
}
 
// ... controller code
~~~

Sometimes you want to do something if ANY error occurs.
Use the following construct to define actions which should take place after the default exception handling.
The best place for this snippet is the first line in the setup() method of your DefaultController. Otherwise all code which throws exceptions before this line would not trigger your actions.

~~~{.php}
$this->debug->setAfterException(function($exception) {
	// your actions
	$this->url->redirect('error/');	
});
~~~


Configuration defaults
--------------

If the framework runs on a host with a toplevel domain, errors will not be outputted to the screen but to a logfile by default.
If you work in a local development environment (like `localhost` or `192.168.1.100`) the other way.

**App/configs/_default.php**
~~~{.php}
...
// debug
	'debug.output.screen'	=> (isset($_SERVER['HOST']) && preg_match('/\.[a-z]+$/', $_SERVER['HOST'])) ? false : true,
	'debug.output.file'		=> (isset($_SERVER['HOST']) && preg_match('/\.[a-z]+$/', $_SERVER['HOST'])) ? true : false,
	'debug.file.path'		=> APP_PATH .'logs/error_'. date('Y-m-d') .'.txt',
...
~~~

Time Handling
--------------

Sometimes it is useful to check several time phases of a project, e.g. for a raffle.
It is helpful to instantiate a DateTime object in the DefaultController so you can imitate every date in your project.

~~~{.php}
<?php
namespace App;
use Morrow\Factory;
use Morrow\Debug;

class PageController extends DefaultController {
	public function run() {
		Factory::prepare('\DateTime', '2012-03-15');

		// Output the current fake date formatted
		$now_formatted = Factory::load('datetime')->format('Y-m-d H:i:s');
		Debug::dump($now_formatted);

		// add a day to the fake date
		Factory::load('datetime')->modify('+1 day');

		// get the timestamp for the fake date +1 day
		$tomorrow_timestamp = Factory::load('datetime')->getTimestamp();
		Debug::dump($tomorrow_timestamp);
	}
}
?>
~~~

Because in the example you are in the controller you could have also used `this->prepare()` instead of `Factory::prepare()` and `$this->datetime` instead of `Factory::load('datetime')`.