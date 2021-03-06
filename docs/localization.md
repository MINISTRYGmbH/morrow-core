Localization
=============================

Handling content for different languages can be a very complex topic.
Morrow simplifies the process as much as possible, but a certain amount of complexity remains, because there are so many different aspects to multilingual sites, not just words, but also use of currency and date formats, for example.

Configuration
-------------

Like many things in Morrow, setting up languages begins with a config variable.
In the config file `app/configs/_app_default.php` you define your languages.
Whether you use a short form like `en` or long one like `english` or even `English` is up to you, but you will have to use exactly the same definitions through out the rest of the project.

**Set up a project for one language**

~~~{.php}
// languages
    'languages'     => ['en'],
~~~

**A multilingual project**

~~~{.php}
// languages
    'languages'     => ['en', 'de'],
~~~

The first language will be the default language and will not appear in the URL path.


For each of the languages you define in your config, you will have to create a folder with the name of the language within the `languages/` folder.
This folder e.g. `languages/de/` must contain these three files:

* `i18n.php` contains translations and is maintained automatically
* `l10n.php` contains global information for this language
* `tree.php` contains the navigation tree structure for the \Morrow\Navigation class

**languages/[lang]/i18n.php**

This file is created and maintained automatically for all languages that are not the default language.

**languages/[lang]/l10n.php**

The `l10n.php` file contains global configuration variables that apply only to this language. This includes the name of the language, the date and the currency formats. You can extend the definitions for your own purposes, but the provided keys should be defined in any case, since Morrow needs them.

~~~{.php}
<?php

return [
	'key'		=> 'de',
	'keys'		=> ['deu_deu','de_DE.utf-8','de_DE','de_de','de'], // used for user language recognition and set_locale
	'title'		=> 'Deutsch',
	'timezone'	=> 'Europe/Berlin',
	'date'		=> ['separator' => '.', 'order' => 'DMY', 'format' => '%d. %B %Y'],
	'currency'	=> ['separator' => ',', 'thou' => '.'],
];

?>
~~~

**languages/[lang]/tree.php**

For an explanation of this file take a look at the \Morrow\Navigation class.


The workflow
-------------

Throughout the framework you will use the method `Factory::load('language')->_('YOUR TEXT')` (or `~~:_('YOUR TEXT')~` in templates) to define text you want to have multilingual.
In parenthesis you write your text in the default language.
If you request a page in a language that is not the default language the language class will try to find the translation for the text in its `i18n.php` file.
If it cannot find the translation a crawler will search all calls to a method with an underscore within the whole framework (*.php and *.htm files) and will add unknown found texts to the `i18n.php` file.
Now you can translate them.

Language Dependent Templates
----------------------------

Sometimes you need different HTML templates for individual languages or you have so much text, that putting it all in variables would be time consuming or too confusing.
The default view handler \Morrow\Views\Serpent gives you a simple way of creating templates for different languages. Simply add the language-key to the name of the template file: `[alias].[lang].htm`.

**Default template:** `home.htm`
**German template:** `home.de.htm`
