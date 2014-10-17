Configuration
============

Before we start we want to give you an overview about the configuration handling in Morrow because some other pages refer to this knowledge.

The access to the Configuration is handled by the \Morrow\Config class.
This class automatically loads all the configuration files in the folder `app/configs/` in a specific order whereby the next file extends the previous.


app/configs/_default.php
------------

This file will always be loaded and contains all configuration parameters the framework needs to run.
If you want to modify defaults for your project (like routes), use the `_default_app.php`.

> You should never change anything in this file. 

app/configs/_default_app.php
------------

This is the place where you put your parameters that are specific for your project (e.g. routes, third party API keys and so on).
This file extends the `_default.php` so you just have to specify rules that are different from the defaults.


app/configs/[HOSTNAME_OR_IP].php
------------

You will often have the case where you need to modify configuration parameters on specific machines, for example your `localhost` or your staging environment.

These files will extend the configuration at least. 
Use for example a file `localhost.php` or `127.0.0.1.php` to override parameters for your local development server.
Keep in mind that at first the file with the hostname will be loaded and at least the one with the IP address.


The specific order
------------------

So the order is  

`app/configs/1.2.3.4.php`  
extends  
`app/configs/www.example.com.php`  
extends  
`app/configs/_default_app.php`  
extends  
`app/configs/_default.php` (required)  


Dot Syntax
----------

This class works with the extended dot syntax. So if you use keys like `mailer.host` and `mailer.smtp` as identifiers in your config, you can call `$this->Config->get("mailer")` to receive an array with the keys `host` and `smtp`. 
