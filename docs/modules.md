_Modules_
=========

Each application you create with Morrow will consost of at least one _Module_. However, we strongly recommend to utilize this feature by creating multiple, reusable abstract _Modules_ to simplify the creation of future application.

For example, if you need a user login and logout, you may consider to create a _Module_ explictly designed for that purpose. This way, you will be able to reuse this functionality in future application with very little effort.


Registering _Modules_
---------------------
Any _Module_ may be executed at any time with any of its controllers. To handle this, Morrow provides a configuration file:

* `modules/modules.php`

This configuration is a multidimensional array, just like other Morrow-Configutations.
To register a _Module_, you have to specify certain parameters.

### _Modules-Array_ Structure
**modules/modules.php**
~~~{.php}
<?php

$modules = [
	'Page-Selector' => [ // Page-Selector-Array
		'Timing-Type' => [ // Timing-Type-Array
			[ // Controller-Array
				'action'   => 'DOM-Action',
				'class'    => 'Module-Controller',
				'selector' => 'DOM-Action-CSS-Selector',
			],
		],
	],
];

/* modify here
********************************************************************************************/
foreach($modules as $regex => $module){
	// ... modify here
}

return $modules;

?>
~~~

### _Page-Selector_
This parameter is a key of a _Page-Selection-Array_ and specifies a certain selection of pages. It is a regular expression which will be matched against the requested URL relative to the project root. If it matches, all _Module-Controllers_ specified inside this array will get called. The _Page-Selection-Array_ may contain two _Timing-Type-Arrays_.

### _Timing-Type_
This parameter is a key of a _Timing-Type-Array_ and specifies when the _Module-Controllers_ get called. Possible values are `pre` and `post`.

Controllers inside a _Timing-Type-Array_ with the key `pre` are _Pre-Controllers_ and are called before the _Page-Controller_. For example, _Pre-Controllers_ are a great way to define event-listeners. This enables you to create a simple API for each _Module_, so any _Page-Controller_ may interact with any _Module-Controller_. _Pre-Controllers_ may not manipulate the site-output because no instance of the DOM-Class has been created at this time.

Controllers inside a _Timing-Type-Array_ with the key `post` are _Post-Controllers_ and are called after the _Page-Controller_.

Each _Timing-Type-Array_ may contain multiple _Controller-Arrays_. Their order of execution inside the _Timing-Type-Array_ itself
is determined by their numeric key.

### DOM-Action
This optional parameter specifies, with which action the generated output should be put into the existing DOM. Possible values are `prepend`, `append`, `replace`. If you specify a _DOM-Action_, you also have to specify a _DOM-Action-_





* target pages to execute the _Module_ on
* position in _Execution-Queue_
* called _Module-Controller_
* action and its place of the _DOM-Class_
* config params







Module controllers that are executed before the _Main-Module_ are called _Pre-Controllers_ and are not able to manipulate the created DOM since it will be created by the _Main-Module_ in the first place. Module controllers that are executed after the _Main-Module_ are - as you already might have guessed - called _Post-Controllers_ and may manipulate the DOM.
