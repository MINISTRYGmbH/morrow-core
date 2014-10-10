Modularity with Features
======================

The feature *Features* is the most exciting feature in the Morrow framework. OK, seriously, it is very nice.
If you are familiar with agile methods likes [Scrum](http://en.wikipedia.org/wiki/Scrum_(software_development)), [Extreme Programming](http://en.wikipedia.org/wiki/Extreme_programming) or [Feature-driven development](http://en.wikipedia.org/wiki/Feature-driven_development) to develop your software you will know that features are well separated from each other in your Feature list.
But your code in an MVC environment is nevertheless a little bit messy because all the code for one webpage which contains many different features is at least squeezed into one controller and one template.

Imagine you have built a CMS and you want to disable the adding of pages.
In a typical MVC scenario you will have to remove some controllers, modify some other controllers by commenting out important lines, modify many templates to remove links to the functionality and so on.
And then you hope that you did not remove an important variable of the controller and everything works.
Now, sometimes it does.

Morrow introduces *Features* you build corresponding to your Feature list.
If you want to add a feature you can do that without interfering other features.
If you later want to remove a feature you can do that in seconds. Without any side effects.

> *Features* will keep your codebase clean and reusable.


Give me some useful examples for useful *Features*
------------------------------------------------

  * Those which are not essential for you website or can simply reused like comment systems, social share buttons, tracking codes ...
  * Code optimizations like minifying the HTML, summarize JS `<script>` tags and CSS `<link>` tags. Put your *Feature* at the end of the `features.php` to get executed at the end so you get the finally rendered HTML.
  * It is also possible to completely build your website with *Features*. So you can recombine your *Features* to build new pages.
  * Access control. If you have build your website completely with *Features* you are able to remove other of your *Features* from the current page while the *Feature* processing queue is running.
  * A/B Tests. Just write a A/B test feature controller that removes the feature you do not need at the moment.
  * There will be a lot more ...
  

How does it work?
----------------
Every *Feature* in Morrow is its own MVC construct which should at least be fully independent from other features.
Think of widgets, but in Morrow you will not include the widget in the template and trigger this way the execution of controllers, models and templates.
It is more like in jQuery where you append functionality to specific HTML DOM elements. So you are also able to extend an existing project without touching the original codebase.

All *Features* are in the folder `app/features/`.


The registration file `app/features/features.php`
-----------------------------------

This file controls which *Feature* is executed on which page.
This is an example your file could look like:

~~~{.php}
<?php

$features = [
	'~^home$~i' => [
		'#canvas' => [
			[
				'action' => 'append',
				'class' => '\\app\\features\\Time\\Simple',
				'config' => ['format' => '%Y-%m-%d']
			],
			...
		],
	],
];


foreach ($features as $regex => $feature) {
	// ... modify here
}

return $features;

?>
~~~

In this example we execute the controller `Simple` from the *Feature* folder `app/features/Time/`.
You will find this *Feature* as an example in the `features/` folder.
The response will be appended to the ID `canvas` of the HTML source, but only if the word `home` is the only node in the URL path.

So the first key (in the example `~^home$~i`) of the array is a regular expression that defines on which pages which *Features* should be executed.
You have to match the alias of the page.
Use the `foreach` loop at the end of the definition to modify the rules in special cases.
Useful if you have a rule like `~.*~` but there is one page where you want the feature not to be processed.
A regular expression to exclude e.g. 10 of 1000 pages would be a litte complicated.

The second key is a CSS selector (in the example `#canvas`) that defines one or many HTML DOM elements we want to modify.
It is also possible to use XPATH 1.0 selectors. For its usage take a look a \Morrow\DOM which is used internally at this point.
Some features do not need to return HTML code, so it wouldn't matter which selector you use. In such a case leave the selector empty.

The second key contains an array of arrays, where each array defines one *Feature*.
As `action` you can use `prepend`, `append`, `after` and `before` to define where the result of the Feature should be written to.
Here is an overview of the positions:

~~~{.php}
[before]
<div id="canvas">
	[prepend]

	<p>Hello world!</p>

	[append]
</div>
[after]
~~~

As `class` you define the class of the Feature that should be executed.
In the example there could exist a second Feature controller with the name "Extended" that output a more complicated display of a clock.
To access this controller you would write `\\app\\features\\Time\\Extended`.

The third key `config` is optional and overwrites configuration parameters of the config that may exist in the Features `config/` folder.

**Important:** All features are processed in the order of their occurence in this file.


Every Feature is an MVC triad
------------------------------

As stated before, every *Feature* is an MVC construct, a little independent world. So in every *Feature* folder you could find `configs/`, `models/`, `public/` and `templates/` folders.
The folder structure for the example looks like this:

~~~
app/features/Time/
	configs/
		_default.php
	models/
		Time.php
	public/
		default.js
	templates/
		Javascript.htm
		Simple.htm
	_Default.php
	Javascript.php
	Simple.php
~~~


An example Feature controller
-----------------------------

Now let's take a look at the example controller `Simple`. It could look like this:

~~~{.php}
<?php

namespace app\features\Time;
use Morrow\Factory;
use Morrow\Debug;

class Simple extends _Default {
	public function run($dom) {
		$time		= new Models\Time;

		$seconds	= Factory::load('Config:feature')->get('seconds');
		$view		= Factory::load('Views\Serpent');
		$view->setContent('seconds', $seconds);

		$dom->append('body', '<script src="features/Time/public/default.js" />');

		return $view;
	}

?>
~~~

Yes, it looks very similar to the usually used controllers in Morrow. There are just little differences:

* The namespace is now `app\features\[FEATURE-NAME]` instead of `app`.
* The method `run()` receives an instance of \Morrow\DOM.
  This instance contains the current state of the HTML.
  So you are able to modify the DOM with the methods \Morrow\DOM provides.
  This is useful if your feature have to do many modifications to the DOM. For example if you want to change all paths of images, scripts, link-rels and so on to CDN paths.
* The instance of the config for your feature is accessible by `Config:feature` instead of `Config`.
* You can also use the public folder of a feature for images, scripts and so on.
Just use a path like this:
`features/Time/public/default.js`

Now take a look at folder `app/features/Time/` and learn how to build a simple feature.


Call a Feature manually
-------------------------------

It's also possible to call a *Feature* in a controller, a template or whereever you need it.

**Keep in mind:** If you call a Feature manually you don't get the DOM passed to the *Feature* because the DOM doesn't exist at that time.


**Controller code**

In this example we pass the rendered HTML of the Feature `Foo\\Bar` to the main template as variable `$widget`.
We also pass an array to the *Feature* to overwrite the configuration variable `foo` of the *Feature*.

~~~{.php}
<?php

$handle = Factory::load('Core\Feature')->run('\\app\\features\\Foo\\Bar', array('foo' => 'bar'));
$this->view->setContent('widget', stream_get_contents($handle));

?>
~~~

**Template code**

In this example we just render the HTML of the *Feature* where we have included this snippet.
This is what you probably think of by the word "widget".

~~~{.php}
<div>
	~~:feature('\\app\\features\\Time\\Simple')~
</div>
~~~


Best practices
---------------

 * Do not write *Features* that interact with other *Features*. If you do this, you cannot securely remove a *Feature* anymore without breaking your application.
 * Avoid inline javascript or stylesheets to allow the usage of CSP headers by the \Morrow\Security class.
 * If you work with date or time, use `Factory::load('\DateTime');` to get the current date and time. This way you can modify the date as described in [Debugging](page/debugging).