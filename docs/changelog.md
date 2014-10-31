Changelog
============

Version 1.0.0 (2014-10-10)
-------------

Initial version. Have fun coding.


Version 1.1.0 (2014-10-17)
-------------

* \Morrow\Config: Made `configs/default.php` required.
* \Morrow\Debug: Removed the setting of the Header `HTTP 500` in case of an exception because of .
* \Morrow\Debug: Changed `debug.output.screen` and `debug.output.file` from boolean to a timestamp to prevent forgetting to disable `debug.output.screen` after working in a live environment.
* \Morrow\DOM: Added methods `exists()`, `query()` and `replace()`.
* \Morrow\DOM: Added `nth-child(...)` to valid CSS selectors.
* \Morrow\DOM: Made the XPATH object public.
* \Morrow\Factory: Added `Factory::onload` so you don't have to set a template path if you create an instance of the \Morrow\Views\Serpent class.
* \Morrow\Factory: Changed magical autoloading so it is now necessary to use the correct spelling of the class as member (`$this->Input` instead of `$this->input`).
* \Morrow\Feature: Added Template mapping closure in `app/_configs/default.php` (`router.template`).
* \Morrow\Feature: Fixed some bugs with the execution of features.
* \Morrow\Frontcontroller: Added `ini_set('display_errors', 'on');` so the debug parameters in the config are always working.
* \Morrow\Frontcontroller: Fixed ETag handling.
* \Morrow\Frontcontroller: If a class cannot be found a RunTimeException is now thrown instead of an Exception.
* Added docs for Configuration and Models and modified many docs.
* Removed `composer.lock`.

Version 1.1.0 (2014-10-17)
-------------

* \Morrow\Features: Fixed `delete()` which does not work.
* Allowed public access to `public/` folders in nested features. So it is now possible to group features in folders.
