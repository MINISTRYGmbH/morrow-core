Features
============

"Features" are the most exciting construct in the Morrow framework.
If you use a agile method like [Scrum](http://en.wikipedia.org/wiki/Scrum_(software_development)), [Extreme Programming](http://en.wikipedia.org/wiki/Extreme_programming) or [Feature-driven development](http://en.wikipedia.org/wiki/Feature-driven_development) to develop your software you will know that features are well separated from each other in your Feature list.
But your code in an MVC environment is nevertheless a little bit messy because all the code for one webpage which contains many different features is at least squeezed into one controller and one template.

Imagine you have built a CMS and you want to disable the adding of pages.
In a typical MVC scenario you will have to remove some controllers, modify some other controllers by commenting out important lines, modify many templates to remove links to the functionality and so on.
And then you hope that you did not remove an important variable of the controller and everything works.
Now, sometimes it does.

Morrow introduces "Feature" constructs you build corresponding to your Feature list. If you want to remove a feature you can do that in seconds.

How does it work?
----------------
Every "Feature" in Morrow is its own MVC construct which should at least be fully independent from other features.
Think of widgets, but in Morrow you will not include the widget in the template and trigger this way the execution of controllers, models and templates.
It is more like in jQuery where you append functionality to specific HTML DOM elements. So you are also able to extend an existing project without touching the original codebase.

Imagine you want the Feature ""



Give me some useful examples for useful "Features"
------------------------------------------------

  * Those which are not essential for you website or can simply reused like comment systems, social share buttons, tracking codes ...
  * Code optimizations like minifying the HTML, summarize JS `<script>` tags and CSS `<link>` tags. Put your "Feature" at the end of the `features.php` to get executed at the end so you get the finally rendered HTML.
  * It is also possible to completely build your website with "Features". So you can recombine your "Features" to build new pages.
  * Access control. If you have build your website completely with "Features" you are able to remove features from the current page while the "Feature" processing queue is running.
  * There will be a lot more ...
  
**If you use "Features" you will keep your codebase clean and reusable.**
