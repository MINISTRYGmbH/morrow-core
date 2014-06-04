Installation
============

The requirements for the Morrow PHP Framework are just an Apache Webserver with the mod_rewrite extension enabled and PHP >= 5.3.8.
You must also have [Composer](http://getcomposer.org/) installed.
If you don't have experience in using Composer, take a look at [Composer - Getting started](http://getcomposer.org/doc/00-intro.md).

Now the installation is as simple as this:
~~~
composer create-project morrow/framework [YOUR_PATH]
~~~
This gives you a clean and empty project to build your next website.

The documentation you read at the moment is also a web project build with the Morrow framework.
To install it on your own webserver you have to do this (but this is of course optional):
~~~
composer create-project morrow/docs [YOUR_PATH]
~~~

Folder structure
----------------

Now you should have the following folder structure:

* `app/` The App folder you are mostly working in
	* `configs/` Configuration files of the framework
	* `languages/` Configuration and translations for the used languages
	* `libraries/` Your own (helper) classes, PSR-0 compatible
	* `models/` Your models for the project
	* `storage/` Temporary files for the project (also log files and error logs)
	* `templates/` Templates for (X)HTML output
* `public/` All data that is accessible by HTTP
* `vendor/` Composer handled libraries


Permissions
---------------

The following folder has to be writable by the web server user:
 
 * `app/storage/`

