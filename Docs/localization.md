Localization
=============================

Handling content for different languages can be a very complex topic. Morrow simplifies the process as much as possible, but a certain amount of complexity remains, because there are so many different aspects to multilingual sites, not just words, but also use of currency and date formats, for example.

Configuration
-------------

Like many things in Morrow, setting up languages begins with a config variable. In the appropriate config file for your project (folder: PROJECT_FOLDER/_config/), you can define your languages (a string for one language, or an array for one or more languages). Whether you use a short form like "en" or long one like "english" or even "English" is up to you, but you will have to use exactly the same definitions through out the rest of the project.

**Set up a project for one language**

~~~{.php}
### languages
$config['languages']= 'en';
~~~

**A multilingual project**

~~~{.php}
### languages
$config['languages'] = array('en','de');
~~~

The first language will be the default language and will not appear in the URL path!


The Folder _i18n
-----------------

For each of the languages you define in your config, you will have to create a file with the name language.php and an folder with the name of the language within the _i18n folder.

### The Language PHP File

The PHP file contains further configuration variables that apply only to this language. This includes the name of the language, the date and the currency formats. You can extend the definitions for your own purposes, but the provided keys should be defined in any case, since Morrow needs them.

~~~{.php}
 $config = array(
    'key' => 'de',
    'keys' => array('deu_deu','de_DE.utf-8','de_DE','de_de','de'), // used for user language recognition and set_locale
    'title' => 'Deutsch',
    'timezone' => 'Europe/Berlin',
    'date' => array('separator' => '.', 'order' => 'DMY', 'format' => '%d. %B %Y'),
    'currency' => array('separator' => ',',     'thou' => '.'),
    'translations' => array(
        'year' => array('Jahr', 'Jahren'),
        'month' => array('Monat', 'Monaten'),
        'week' => array('Woche', 'Wochen'),
        'day' => array('Tag', 'Tagen'),
        'hour' => array('Stunde', 'Stunden'),
        'minute' => array('Minute', 'Minuten'),
        'second' => array('Sekunde', 'Sekunden'),
    )
);
~~~

The Language Folder
-------------------

The language folder must contain the files global.php and tree.php. The first is for language definitions that extend over all pages. The second contains the definition of the project navigation tree, which has it's own chapter: Navigation. Furthermore you can define contents that are only for individual pages by creating files that have the page alias as their name (_i18n/language/alias.php)

### Global / Page Language Content

Basically there are only two variables that can be used in the language files: $content and $form. The second variable is explained in the context of Form Handling. For all other purposes use $content, which is later accessible by using the Language.Class .

~~~{.php}
$content['greeting'] =  'Hello!';
~~~


Passing Language Content to View
--------------------------------

In order to pass content to your view handler, you have seen that you must call the view method setContent. For language content it is the same, but first you must retrieve it. For this purpose there is the method getContent in the Language class, which requires the page alias of the content you want (usually it is the alias of the current page).

~~~{.php}
... Controller code

$alias = $this->page->get('alias');
$language_content = $this->language->getContent($alias);
 
// pass the content to the view handler with the variable name 'language'
$this->view->setContent($language_content, 'language');

... Controller code
~~~

Language Dependent Templates
----------------------------

Sometimes you need different HTML templates for individual languages or you have so much text, that putting it all in variables would be too time consuming.

Morrow give you a simple way of creating templates for different languages. Simply add the language-key to the name of the template file: alias.lang.htm.

**Default template:** home.htm

**German template:** home.de.htm
