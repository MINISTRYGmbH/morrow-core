Extending MorrowTwo
====================

Sometimes you will reach a point where you need more power. No problem.
You can integrate your own classes, replace any file of the MorrowTwo framework or integrate other libraries via [Composer](http://getcomposer.org/).
If you don't have experience in using Composer, take a look at [Composer - Getting started](http://getcomposer.org/doc/00-intro.md).

Beside your `App` folder you will find those two folders:

* `vendor/` Maintained by Composer. Don't change anything here manually.
	* `composer.json` The Composer control file.
* `vendor_user/` The folder to replace Morrows core components or load Not-Composer-Libraries.

Morrow uses the autoloader of Composer and is fully [PSR-0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md) and [PSR-4](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md) compatible.


Working with Composer libraries
-------------------------------
We assume that you know what Composer and Packagist is and how to work with it.

If you have added `"michelf/php-markdown": "1.3.*"` to your composer.json file and have updated Composer, you can use it instantly like this:

~~~{.php}
<?php
namespace App;
use Morrow\Factory;
use Morrow\Debug;

class PageController extends DefaultController {
	public function run() {
		
		// use it directly
		$content = ...;
		$content = \Michelf\MarkdownExtra::defaultTransform($content);

		// or use the Factory to create an instance
		$content = ...;
		$content = Factory::load('\Michelf\MarkdownExtra')->defaultTransform($content);
	}
}
?>
~~~


Working with Not-Composer-Libraries
------------------------------------
Assuming you want to install the Markdown library of the example above by hand, you could also download the ZIP package from http://michelf.ca/projects/php-markdown/.
Copy the folder `Michelf` of the ZIP into the `vendor_user/` folder und you are done. Thanks to PSR-0.

If the library you want to use is not PSR-0 compatible just copy it into a subfolder in `vendor_user/`.
Then you have to include the correct files manually, just like in the "good" old days.
But this is not recommended because you have to take care of missing class dependencies.


Replacing or adding MorrowTwo components
--------------------------------------------
All classes namespaced with `Morrow\` are first searched in the `vendor_user/` folder and then in the `vendor/` folder.

So if you have found MorrowTwo bugs and you can't wait for the official bugfix, it is possible to replace any file of the MorrowTwo framework with your own files.
If you e.g. want to replace the \Morrow\Benchmark class, copy the class from `vendor/Morrow/Benchmark.php` to `vendor_user/Morrow/Benchmark.php`.
Now the file in `vendor_user/` is preferred and can be modified.

This way you can also add new classes you want to have in the `Morrow\` namespace. 
Just copy them into `vendor_user/Morrow/`.


Create your own classes
------------------------
If you work with folder paths just expect folders to have a trailing slash when the user passes one and only return paths in such a format.
And do not forget that your class is responsible to create the folder in the `STORAGE_PATH`, so the user could remove all folders from it.

