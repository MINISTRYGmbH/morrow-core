Changelog
============

Version 1.0.0 (2014-10-10)
-------------

Initial version. Have fun coding.


Version 1.1.0 ()
-------------

* \Morrow\Feature: Added Template mapping closure in `app/_configs/default.php` (`router.template`).
* \Morrow\Frontcontroller: Added `ini_set('display_errors', 'on');` so the debug parameters in the config are always working
* \Morrow\Factory: Added `Factory::onload` so you don't have to set a template path if you create an instance of the \Morrow\Views\Serpent class
* \Morrow\Factory: Changed magical autoloading so it is now necessary to use the correct spelling of the class as member (`$this->Input` instead of `$this->input`)
* \Morrow\Debug: Removed the setting of the Header `HTTP 500` in case of an exception because of .
* \Morrow\Debug: Changed `debug.output.screen` and `debug.output.file` from boolean to a timestamp to prevent forgetting to disable `debug.output.screen` after working in a live environment.
* Added docs for Configuration and Models.
* Removed `composer.lock`.
