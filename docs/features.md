Modularity with Bricks
======================

Bricks are the most exciting feature in the Morrow framework.
If you use a agile method like [Scrum](http://en.wikipedia.org/wiki/Scrum_(software_development)), [Extreme Programming](http://en.wikipedia.org/wiki/Extreme_programming) or [Feature-driven development](http://en.wikipedia.org/wiki/Feature-driven_development) to develop your software you will know that features are well separated from each other in your Feature list.
But your code in an MVC environment is nevertheless a little bit messy because all the code for one webpage which contains many different features is at least squeezed into one controller and one template.

Imagine you have built a CMS and you want to disable the adding of pages.
In a typical MVC scenario you will have to remove some controllers, modify some other controllers by commenting out important lines, modify many templates to remove links to the functionality and so on.
And then you hope that you did not remove an important variable of the controller and everything works.
Now, sometimes it does.

Morrow introduces Bricks you build corresponding to your Feature list.
If you want to add a feature you can do that without interfering other features.
If you later want to remove a feature you can do that in seconds. Without any side effects.

> Bricks will keep your codebase clean and reusable.


Give me some useful examples for useful "Features"
------------------------------------------------

  * Those which are not essential for you website or can simply reused like comment systems, social share buttons, tracking codes ...
  * Code optimizations like minifying the HTML, summarize JS `<script>` tags and CSS `<link>` tags. Put your "Feature" at the end of the `features.php` to get executed at the end so you get the finally rendered HTML.
  * It is also possible to completely build your website with "Features". So you can recombine your "Features" to build new pages.
  * Access control. If you have build your website completely with "Features" you are able to remove other of your "Features" from the current page while the "Feature" processing queue is running.
  * There will be a lot more ...
  

How does it work?
----------------
Every "Feature" in Morrow is its own MVC construct which should at least be fully independent from other features.
Think of widgets, but in Morrow you will not include the widget in the template and trigger this way the execution of controllers, models and templates.
It is more like in jQuery where you append functionality to specific HTML DOM elements. So you are also able to extend an existing project without touching the original codebase.

All "Features" are in the folder `app/features/`.


The registration file `app/features/features.php`
-----------------------------------

This file controls which "Feature" is executed on which page.
This is an example your file could look like:

~~~{.php}
<?php

return array(
	'~^home$~i' => array(
		'#canvas' => array(
			array('append' => '\\app\\features\\Time\\Simple'),
			array(...),
		),
	),
);

?>
~~~

In this example we execute the controller `Simple` from the "Feature" folder `app/features/Time/`.
You will find this "Feature" as an example in the `features/` folder.
The response will be appended to the ID `canvas` of the HTML source, but only if the word `home` is present on the URL path.

So the first key (in the example `~^home$i~`) of the array is a regular expression that defines on which pages which "Features" should be executed.

The second key is a CSS selector (in the example `#canvas`) that defines one or many HTML DOM elements we want to modify. It is also possible to use XPATH 1.0 selectors. For its usage take a look a \Morrow\DOM which is used internally at this point.

At least we define in which way the DOM elements should be modified. You can use `prepend`, `append`, `after` and `before`.
Here an overview of the positions:

~~~{.php}
[before]
<div id="canvas">
	[prepend]

	<p>Hello world!</p>

	[append]
</div>
[after]
~~~

**Important:** All features are processed in the order of their occurence in this file.


Every Feature is an MVC triad
------------------------------

As stated before, every "Feature" is an MVC construct, a little independent world. So in every "Feature" folder you could find `configs/`, `models/`, `public/` and `templates/` folders.
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
		$time	= new Models\Time;
		$format	= $this->config->get('app.features.time.format');

		$view = Factory::load('View:view-feature');
		$view->setContent('time', $time->get());
		$view->setContent('format', $format);
	}
}

?>
~~~

Yes, it looks very similar to the usually used controllers in Morrow. There are just little differences:

* The namespace is now `app\features\[FEATURE-NAME]` instead of `app`.
* The method `run()` receives an instance of \Morrow\DOM.
  This instance contains the current state of the HTML.
  So you are able to modify the DOM with the methods \Morrow\DOM provides.
  This is useful if your feature have to do many modifications to the DOM. For example if you want to change all paths of images, scripts, link-rels and so on to CDN paths.
* The config of you feature is embedded into the main config and is accessible by `app.features.[FEATURE-NAME]`.
* The instance of the view for your feature is accessible by `View:view-feature` instead of `View`.
* You can also use public folders for images, scripts and so on that should be public accessible.
Just use a path like this:
`features/Time/public/default.js`

Now take a look at folder `app/features/Time/` and learn how to build a simple feature.
