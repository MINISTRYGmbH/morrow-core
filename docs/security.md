Security
=============================

Morrow have some measures builtin to improve the protection against attackers.
But they only work if you use them.
So this is a little guide to assist you in building safe applications.


Sessions
-----------------------------
Morrows \Morrow\Session class regenerates the session ID every time the User Agent changes.
So the attacker have to know the User Agent of his victim.

But nevertheless you should always use PHPs `session_regenerate_id()` after any privilege level change, e.g. at every login.

*More on <https://www.owasp.org/index.php/Session_Management_Cheat_Sheet>.*

By default Morrows session cookie is set to `httponly`.
That prevents Javascript to access the session cookie and makes XSS attacks on the session cookie useless.

*More on <https://www.owasp.org/index.php/HttpOnly>*

If you have an application or a page which only relies on HTTPS you should add the `secure` flag to the session cookie parameters so the cookie will only be sent over secure connections.
To do this, you just have to set the corresponding parameter in

**app/configs/_default_app.php**
~~~{.php}
'session.cookie_secure'		=> true,
~~~


XSS
------------------------------- 
If you use the view handler "Serpent" all content that is set via `this->view->setContent()` is changed.
The string `Foobar` becomes `<!XSS!>Foobar<!/XSS!>`.

So if you use a variable in a template you first have to declare its intended use, otherwise you will get an Exception.
You have to wrap the variable in one of two template functions which will remove the `<!XSS!>` tags.
That way you cannot forget to think about XSS when you build applications.

* Use `:raw()` if you know that the input cannot contain an XSS attack.
* Use `:escape()`  to escape user input.

**template.htm**
~~~{.php}
/* Throws an Exception */
~~$page.base_href~

/* Works */
~~:escape($page.base_href)~
~~:raw($page.base_href)~
~~~

*More on <https://www.owasp.org/index.php/Cross-site_Scripting_(XSS)>*


CSP (Content Security Policy)
-------------------
The Content Security Policy (CSP) is an added layer of security that prevents XSS and data injection attacks.
With CSP you can control which resources are allowed to load from where.

In Morrow the CSP HTTP headers are set by default. You can modify the rules in your

**_configs/_default_app.php**
~~~{.php}
// security
	'security.csp.default-src'		=> "'self'",
	// 'security.csp.script-src'	=> "'self'",
	// 'security.csp.img-src'		=> "'self'",
	'security.csp.style-src'		=> "'self' 'unsafe-inline'",
	// 'security.csp.media-src'		=> "'self'",
	// 'security.csp.object-src'	=> "'self'",
	// 'security.csp.frame-src'		=> "'self'",
	// 'security.csp.font-src'		=> "'self'",
~~~

*More on <https://www.owasp.org/index.php/Content_Security_Policy>*


CSRF
-----------------------
CSRF is an attack which forces an user to execute unwanted actions on a web application in which he/she is currently authenticated.

Let's assume you have a button which allows to delete a dataset. The link looks like this:
`http://domain.com/cms/product/34?delete=true`

An attacker could change the ID and delete other datasets. Now, all datasets the authenticated user is allowed to.
The only valid counteragent is to use a server generated token which is used for all data changing URLs and generated for every user session (and is NOT the users session id otherwise we would create a session highjacking hole).

\Morrow\Security offers three methods to work with CSRF tokens.

**Controller**
~~~{.php}
// Get a token for the current user
$token = $this->Security->getCSRFToken();
~~~

~~~{.php}
// create a secure URL with an CSRF token
// This method works exactly as $this->Url->create() but adds the token
$url = $this->Security->createCSRFUrl('cms/product/34', ['delete' => 'true']);
~~~

~~~{.php}
// checks if the token was submitted and is valid
// the URL has to be created by $this->security->createCSRFUrl
$valid = $this->Security->checkCSRFToken();
~~~

In a template just use the mapping `:securl()` instead of `:url()` to create secure URLs.
But do not forget to check the validity of the token in your controller.

*More on <https://www.owasp.org/index.php/Cross-Site_Request_Forgery_(CSRF)>*


Clickjacking
----------------------
If you know that your site won't ever be in a frame or you want so specify who is allowed to show your site in an iframe you should set the X-Frame-Options header to prevent Clickjacking.

In Morrow the `X-Frame-Options` header is set by default. You can modify the value in your

**_configs/_default_app.php**
~~~{.php}
// security
	'security.frame_options'		=> "DENY", // (DENY|SAMEORIGIN|ALLOW-FROM uri)
~~~

*More on <https://www.owasp.org/index.php/Clickjacking>*


MIME Type Sniffing
------------------

The only defined value, `nosniff`, prevents Internet Explorer and Google Chrome from MIME-sniffing a response away from the declared content-type.
This also applies to Google Chrome, when downloading extensions.
This reduces exposure to drive-by download attacks and sites serving user uploaded content that, by clever naming, could be treated by MSIE as executable or dynamic HTML files.
If you want to specify who is allowed to show your site in an iframe you should set the X-Frame-Options header to prevent Clickjacking.

In Morrow the `X-Content-Type-Options` header is set to `nosniff` by default. You can modify the value in your

**_configs/_default_app.php**
~~~{.php}
// security
	'security.content_type_options'	=> "nosniff",
~~~

Set the value to an empty string to disable this security related header. But you really shouldn`t do this. Try first to configure your webserver to output correct MIME types.

*More on <https://www.owasp.org/index.php/List_of_useful_HTTP_headers>*


