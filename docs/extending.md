Extending Morrow
====================

Sometimes you will reach a point where you need more power. No problem.
You can add your own classes, load other libraries via [Composer](http://getcomposer.org/) or hotfix any file of your Composer loaded libraries. 
If you don't have experience in using Composer, take a look at [Composer - Getting started](http://getcomposer.org/doc/00-intro.md).

For extending these files and folders in your projects folder (e.g. `main/`) are important:

* `composer.json` Controls the Composer loaded files in `vendor/`.
* `vendor/` Maintained by Composer. Don't change anything here manually.
* `app/libraries/` The PSR-0 controlled folder to load your own classes (e.g. helper classes), to load Non-Composer-Libraries or to hotfix Composer loaded libraries.


Working with Composer libraries
-------------------------------
We assume that you know what Composer and Packagist is and how to work with it.

If you have added `"michelf/php-markdown": "1.4.*"` to your composer.json file and have updated Composer, you can use it instantly like this:

~~~{.php}
// ... Controller code

// load markdown content from file	
$content = file_get_contents('example.md');

// use it directly
$content = \Michelf\MarkdownExtra::defaultTransform($content);

// or use the Factory to create an instance
$content = Factory::load('\Michelf\MarkdownExtra')->defaultTransform($content);

// ... Controller code
~~~

Just take care of the starting slash in front of the foreign class because you are in the namespace `App`.

You will find an excellent list of other useful PHP libraries at [https://github.com/ziadoz/awesome-php](https://github.com/ziadoz/awesome-php).


Working with Non-Composer-Libraries
------------------------------------
Assuming you want to install the Markdown library of the example manually, you could also download the ZIP package from [http://michelf.ca/projects/php-markdown/](http://michelf.ca/projects/php-markdown/).
Copy the folder `Michelf` of the ZIP into the `app/libraries/` folder und you are done. Thanks to PSR-0.

If the markdown library would not have been PSR-0 compatible you just would have copied it nevertheless into a subfolder in `app/libraries/`.
Then you would have to include the correct files manually, just like in the "good" old days.
But this is not recommended because you have to take care of class dependencies.

This is also the right place for your own classes and helpers.
Just organize them here as you like.
Just keep in mind that namespaces are mapped to folders (PSR-0) like this:

`app/libraries/Johndoe/Funky.php`
~~~{.php}
<?php
namespace Johndoe;

class Funky {
	...
}
?>
~~~


Hotfix Composer loaded libraries
--------------------------------------------
If you find a an error in a class and you can't wait for the official bugfix, it is possible to hotfix any file in a Composer loaded library without touching the original file.

Let's assume you want to fix the \Morrow\View class.
First you have to find out how the maintainer decided to autoload its library. Just take a look at the `composer.json` file in the library folder in `vendor/`.
In case of the Morrow PHP Framework it is:

~~~
    "psr-4": {
        "Morrow\\": "src/"
    }
~~~

Copy the PSR-4 rule to the `composer.json` file in your project folder (e.g. `main/`), adjust **only** the path and do a `composer update`.
In a default installation your `composer.json` should now look like this:

~~~
    "autoload": {
        "psr-4": {
            "App\\Models\\": "app/models/",
    	    "Morrow\\": "app/libraries/Morrow/"
        }
    }
~~~

Now every file you would generate in `app/libraries/Morrow/` gets preferred to the original file in `vendor/`.
So in `app/libraries/Morrow/View.php` you could write your fixes for the \Morrow\View class.

**But be warned: Don't forget your hotfix. Hotfixing a library this way could make it break at the next `composer update`.**


Trailing-Slash-Convention
------------------------
If your class works with file system paths it should expect folders to have a trailing slash when the user passes them and should only return paths in such a format.
That makes it all simpler for us when working with the framework.

