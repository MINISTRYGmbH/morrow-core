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
