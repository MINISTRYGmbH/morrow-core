Caching
===============

Caching is in most applications a simple way to gain performance.
Morrow have two mechanisms to make caching as simple as possible for you.

HTTP Caching
------------
The output of every view handler can be cached via HTTP headers.
The caching control is the job of the \Morrow\View class which is accessable via `$this->view` in your controller.
It works with Expiration HTTP headers as defined in RFC 2616.

Morrow uses by default the `ETag` header.
That saves bandwidth, as a web server does not need to send a full response if the content has not changed.
You don't have to do anything to profit by it.

If you need a harder caching it is possible to tell the browser how long it should not resend a request.
Because there is no HTTP request to the server you do not have control over the cache until the cache expires.

### Example

In the following example the output will be cached for five seconds.

~~~{.php}
$this->view->setCache('+5 seconds');
~~~

The passed string defines the lifetime of the cache, given as a string `strtotime()` recognizes. 

Object caching
--------------
It is often useful to cache the results of time consuming tasks, e.g. database results, external JSON or XML requests and so on.
That can be easily done with the \Morrow\Cache class which provide examples to do this.