Multiple Sites
=============================

The skeleton you have downloaded can be used to setup several sites (installations, frameworks, whatever). Not only Morrow projects.

Different sites
------------------

Take a look at the skeleton. First you have two folders: `main/` and `docs/`. Both folders contain fully independent sites although both are build with the Morrow framework.
The `main` site will not appear in the URLs of the site. It is the default site.
All other sites will only be reachable by including the site name in the URL path of the site.
This was defined in the `.htaccess` file.

So to call the homepage of `main` site you would call `http://localhost/skeleton-path/`.  
To call the homepage of the `docs` site you would call `http://localhost/skeleton-path/docs/`

You can name the folders however you like, but these names have to be configured in the `.htaccess` file.




~~~
RewriteEngine on

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
	# Sometimes you need different rules from dev to prod or staging server (e.g. if you need a RewriteBase).
	# You have to adapt the rules below to simulate the RewriteBase.


# Set rewrite base for a TLD
# The slash before main simulates a "RewriteBase /"
RewriteCond %{HTTP_HOST} \.[a-z]{2,}$
RewriteRule ^(?!docs/)(.*)$ /main/$1?morrow_basehref_depth=2 [QSA]

# Set rewrite base for development URLs (simple host names and IPs)
RewriteCond %{HTTP_HOST} !\.[a-z]{2,}$
RewriteRule ^(?!docs/)(.*)$ main/$1?morrow_basehref_depth=2 [QSA]
~~~