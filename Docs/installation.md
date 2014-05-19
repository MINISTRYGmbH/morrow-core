Installation
============

Requirements
-------------

* Apache Webserver (mod_rewrite required)
* PHP >= 5.3.*

Extract
-------

Extract the downloaded package to a folder of your choice below the document root of your webserver. You should have the following file structure:

* `main/` Project folder
	* `app/` The App folder you are mostly working in
		* `configs/` Configuration files of the framework
		* `languages/` Configuration and translations for the used languages
		* `models/` Your models for the project
		* `storage/` Temporary files for the project (also log files and error logs)
		* `templates/` Templates for (X)HTML output
	* `public/` All data that is accessible by HTTP
	* `vendor/` Composer handled libraries


Permissions
---------------

The following folders have to be readable & writable by the web server user:
 
 * `main/app/storage/`

If you have more than one MorrowTwo project (beside `main/`) you have to repeat this step for each of them.

