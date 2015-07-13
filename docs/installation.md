Installation
============

The requirements for the Morrow PHP Framework are just an Apache Webserver with the mod_rewrite extension enabled and PHP >= 5.4.
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

Granting Permissions
-----------------

Morrow will write temporary files into the following folder: `storage/`

Please ensure that this folder will be writable by the web server user.
