Caching
===============

Caching is in most applications a simple way to gain performance.
Morrow have two mechanisms to make caching as simple as possible for you.

HTTP Caching
------------
The output of every view handler can be cached via HTTP headers.
The caching control is the job of the \Morrow\Header class which works with Expiration HTTP headers as defined in RFC 2616.

Object caching
--------------
It is often useful to cache the results of time consuming tasks, e.g. database results, external JSON or XML requests and so on.
That can be easily done with the \Morrow\Cache class which provide examples to do this.