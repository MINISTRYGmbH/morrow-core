Models
======

> A Model [...] represents the underlying, logical structure of data in a software application and the high-level class associated with it. This object model does not contain any information about the user interface.
>
> *Margaret Rouse (whatIs.com)*

Models are just simple classes that are loaded by Composer. They contain all of your logic that affect your data.
All models are located in the folder `models/` and are the only files there.
In this folder you will find the file `Example.php` which includes an example model.

**app/models/Example.php**
~~~{.php}
<?php

namespace app\models;
use Morrow\Debug;
use Morrow\Factory;

class Example extends Factory {
	public function __construct() {
		Debug::dump('Model "Example" found.');
	}
}

?>
~~~

The name space for a model is `app/models`. So you can use this in your controller to get an instance of your model:

**app/Foobar.php**
~~~{.php}
$Example = new models\Example;
~~~

If you are used to the Table Data Gateway pattern, Morrow also provides an abstract [Table Data Gateway](object/Morrow/AbstractTableDataGateway) class to assist you.
