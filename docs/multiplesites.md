Multiple Sites
=============================

This solution to handle multiple sites can be used to setup several projects (installations, frameworks, whatever). Not only Morrow projects.
We advise you to use this structure for all your projects. That way you will not get into trouble if your customer suddenly wants to run multiple projects with only one domain.

It is actually simple. You just have to put your projects into subfolders. An .htaccess file will route the requests to the correct project.
Imagine you want to run a main project (e.g. a Morrow project) but you also want to run the Morrow documentation project at the same domain.
Both folders contain fully independent sites although both are build with the Morrow framework.
You have to setup a folder structure like this:

  * `YOUR_DOCUMENT_ROOT/`
    * `.htaccess`
    * `docs/`
  	* `main/`

Now put this content into the **.htaccess** file:

~~~
	# This htaccess is for separating projects (subfolders).
	# It is not used if the document roots are pointing to the projects "public" folders.
	#
	# One folder (in the examples "main") is the main project and reachable without specifying a subfolder.
	# The other projects are reachable as there would not be this htaccess, but you have to specify them in the RewriteRule.
	#
	# Examples
	# --------
	# RewriteRule ^(.*)$ ...
	# RewriteRule ^(?!wordpress/)(.*)$ ...
	# RewriteRule ^(?!wordpress/|typo3/)(.*)$ ...
	#

RewriteEngine on
	# RewriteBase /

RewriteRule ^(?!docs/)(.*)$ main/$1?morrow_basehref_depth=2 [QSA]
~~~

The `main` site will not appear in the URLs of the site. It is the default site.
All other sites will only be reachable by including the folder name in the URL path of the site.

So to call the homepage of `main` site you would call `http://example.com/`.  
To call the homepage of the `docs` site you would call `http://example.com/docs/`.

You can name the folders however you like and create how many you want, but these folders names have to be configured in this `.htaccess` file.


