Debugging
=============================

Debugging is of course one of the most interesting topics while developing. Morrow gives you a simple and clean way to debug your applications.

Dumping variables
-----------------

The most interesting tool is Morrow's system wide replacement for print_r() and var_dump(). It returns a nice layout with a lot more of information than other tools. For example where you did the call. Never forget anymore where you have placed your debugging calls. Just try out.

~~~{.php}
<?php
namespace app;
use Morrow\Factory;
use Morrow\Debug;

class Foobar extends _Default {
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

Morrow's preferred way is to work with exceptions. For that reason errors throw exceptions, so you can catch them as you would do with normal exceptions. Furthermore we integrated a state-of-the-art-top-level-exception-handler&trade;.

~~~{.php}
try {
    echo $undefined; // yes, the variable $undefined was not defined before
} catch (Exception $e) {
    die($e->getMessage());
}
~~~

Sometimes you want to do something if ANY error occurs.
Use the following construct to define actions which should take place after the default exception handling.
The best place for this snippet is the first line in the setup() method of your default controller. Otherwise all code which throws exceptions before this line would not trigger your actions.

~~~{.php}
$this->Event->on('core.after_exception', function($e, $exception){
	// your actions
	$this->Url->redirect('error/');	
});
~~~


Configuration defaults
--------------

If the framework runs on a host with a toplevel domain, errors will not be outputted to the screen but to a logfile by default.
If you work in a local development environment (like `localhost` or `192.168.1.100`) the other way.

**app/configs/_default.php**
~~~{.php}
...
// debug
	'debug.output.screen'	=> (isset($_SERVER['HTTP_HOST']) && preg_match('/\.[a-z]+$/', $_SERVER['HTTP_HOST'])) ? strtotime('-1 day') : strtotime('+1 day'),
	'debug.output.file'		=> (isset($_SERVER['HTTP_HOST']) && preg_match('/\.[a-z]+$/', $_SERVER['HTTP_HOST'])) ? strtotime('+1 day') : strtotime('-1 day'),
	'debug.file.path'		=> APP_PATH .'logs/error_'. date('Y-m-d') .'.txt',
...
~~~

We don't work with booleans here because it is more safe to let the developer define a date until errors should be logged.
So developers that activate logging on screen in a live environment often forget to disable it after they have to done their work.
So you have to set a date until the error should appear on screen.

**app/configs/example.com.php**
~~~{.php}
...
// debug
	'debug.output.screen'	=> strtotime('2014-10-13 13:20:00'),
...
~~~

Do not use something like `strotime('+1 hour');` because you would of course undermine this security measure.


Date & Time Handling
--------------

Sometimes it is useful to check several time phases of a project, e.g. for a raffle.
It is helpful to instantiate a native DateTime object in the default controller so you can simulate every date in your project.

~~~{.php}
<?php
namespace app;
use Morrow\Factory;
use Morrow\Debug;

class _Default extends Factory {
	public function run() {
		Factory::prepare('\DateTime', '2012-03-15');

		// Output the current fake date formatted
		$now_formatted = Factory::load('\DateTime')->format('Y-m-d H:i:s');
		Debug::dump($now_formatted);

		// add a day to the fake date
		Factory::load('\DateTime')->modify('+1 day');

		// get the timestamp for the fake date +1 day
		$tomorrow_timestamp = Factory::load('\DateTime')->getTimestamp();
		Debug::dump($tomorrow_timestamp);
	}
}
?>
~~~
