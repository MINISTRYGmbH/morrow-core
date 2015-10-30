_Modules_
=========

Each application you create with Morrow will consist of at least one _Module_. However, we strongly recommend to utilize this feature by creating multiple, reusable abstract _Modules_ to simplify the creation of future application.

For example, if you need a user login and logout, you may consider to create a _Module_ explictly designed for that purpose. This way, you will be able to reuse this functionality in future application with very little effort.


Creating a _Module_
-------------------
To create a _Module_, you have to take care of a few things. In the following, we will refer to the shipped example module _Clock_.

_Module-Folders_
----------------
Each _Module_ is located inside its own folder. It may have dependencies, for example a library. It even may have dependencies to other _Modules_, but that is not recommended. The _Module-Folder_ is named like the _Module_ itself and so is its namespace. For example, the _Module_ _Clock_ is located in `modules/Clock/` and its base namespace is `app\modules\\Clock`.

_Module-Controllers_
--------------------
To make use of any _Module_, it has to bear at least one controller. They are located just inside the modules' folder. The _Default-COntroller_ of the module _Clock_ `app\modules\\Clock\_Default` is located in: `modules/Clock/`, like all other controller of _Clock_. You may register each _Module-Controller_ in `modules/modules.php`.








Registering a _Module_
----------------------
Any _Module_ may be executed at any time with any of its controllers. To handle this, Morrow provides a configuration file:

* `modules/modules.php`

This configuration is a multidimensional array, just like other Morrow-Configutations.
To register a _Module_, you have to specify certain parameters.

_Modules-Array_ Structure
-------------------------
**modules/modules.php**
~~~{.php}
<?php

$modules = [
	'=Any/Page=i' => [ // Page-Selector
		'post' => [ // Timing-Type
			[ // Controller-Array
				'selector' => '.any.ccs.selector',
				'action'   => 'append',
				'class'    => '\\app\\modules\\Any_module\\Any_Controller',
				'config'   => ['any_config_key' => 'any_config_value'],
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

_Page-Selector_
---------------
This parameter is a key of an array and specifies _where_ the _Module-Controllers_ get called. It is a regular expression which will be matched against the requested URL relative to the project root. If it matches, all _Module-Controllers_ specified inside this array will get called.

_Timing-Type_
-------------
This parameter is a key of an array and specifies _when_ the _Module-Controllers_ get called. Possible values are `pre` and `post`.

Controllers inside arrays with the key `pre` are _Pre-Controllers_ and are called before the _Page-Controller_. For example, _Pre-Controllers_ are a great way to define event-listeners. This enables you to create a simple API for each _Module_, so any _Page-Controller_ may interact with any _Module-Controller_. _Pre-Controllers_ may not manipulate any site-output because no instance of the DOM-Class has been created at this time.

Controllers inside a _Timing-Type-Array_ with the key `post` are _Post-Controllers_ and are called after the _Page-Controller_.


_Controller-Array_
------------------
There may be multiple _Controller-Arrays_ inside any _Timing-Type-Array_. Their order of execution is determined by their numeric key. Every _Controller-Array_ may contain multiple parameters.

The only mandatory parameter `class` specifies the controller that will be called. Just insert the controller class name as a value.

The optional parameter `action` specifies, with which action the generated output should be put into the existing DOM. Possible values are `prepend`, `append`, `replace`. If you specify this parameter, you also have to specify the parameter `selector`. Its value may be any CSS-selector matching the element on which the action should be performed.

In case you need any custom configs when executing a specific controller, you can pass these configs as an array with the parameter `config`. This array will extend the existing _Module-Config_. Please note that, each time you use the `config` param in a controller array, the _same_ _Module-Config_ get extended. We recommend you to use it inside _Pre-Controllers_ when possible.
