Folder Structure
=

Now lets take a look at Morrow's default folder setup:

* `configs/`
* `languages/`
* `libraries/`
* `modules/`
	* `_main/`
		* `configs/`
		* `models/`
		* `public/`
		* `templates/`
* `storage/`
* `vendor/`

Detailed Information
=
####`configs/`
This folder contains all global config files. These settings will affect the whole project.

####`languages/`
This folder contains _i18n_- and _i10n_-files as well as a _tree_-file for any language you set up.

####`libraries/`
You may freely put any libraries and helper files inside here.

####`modules/`
Since Morrow is designed to encourage you to create multiple, reusable modules - this is the place where the code lives. This folder contains all modules added to the project and the _modules.php_ configuration file.

####`modules/_main/`
The default and only mandatory module can be found here. The _main-module_ is the starting point of every Morrow-project, containing at least a controller for each page.

####`modules/_main/configs/`
Every module may have its own configuration file. They work similar to the global settings but will only affect the local module's behaviour.

####`modules/_main/models/`
Put the module specific models in here.

####`modules/_main/public/`
Files in this folder will be publicly accessable. This is the right place for your module assets.

####`modules/_main/templates/`
Put the module specific templates in here.

####`storage/`
Morrow will write temporary files into this folder. It also can be used to place data for your project in custom sub-folders. Those files will not be public.

####`vendor/`
That's the mandatory vendor folder managed by _composer_. Don't touch that one.
