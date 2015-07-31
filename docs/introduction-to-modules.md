Introduction to _Modules_
=========================

One of the key features of Morrow is its focus on modularity. Each project you create will be a compilation of autonomous MVC-triads, so called _Modules_.

With _Modules_ we give you a powerful tool to keep your code base clean and flexible. Features of your application may be implemented through different _Modules_, granting you the ability to freely edit a specified feature without having to worry about side-effects caused by these changes on other functionalities.

Each module contains its own controllers, templates, models, config files and anything else that is needed to run it. Also, _Modules_ can be set up to execute a certain controller at a certain time and maybe even only under certain circumstances. With these capabilities, you are able to freely enable or disable any module with ease, modify any module's execution queue position with ease, at any time.

However, if you don't need any modularity for your application, you don't have to use _Modules_. Well, that was a bit of a lie. The main part of your application is a module too - the only one that is mandatory and it's shipped by default. This so called _Main-Module_ can be found in `modules/_main/`.

The _Main-Module_ is the core of your application's business logic. Each page request leads to the execution of the respective controller located in the _Main-Module_. There might be other modules' optional controllers that also get executed before or after the _Main-Module_, but that one is a must.

To manage and implement modules, Morrow uses a simple configuration array located in `modules/_main/modules.php`. Simply edit this file to set up your _Modules_-configuration!
