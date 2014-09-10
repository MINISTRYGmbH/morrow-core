<?php
/*////////////////////////////////////////////////////////////////////////////////
    MorrowTwo - a PHP-Framework for efficient Web-Development
    Copyright (C) 2009  Christoph Erdmann, R.David Cummins

    This file is part of MorrowTwo <http://code.google.com/p/morrowtwo/>

    MorrowTwo is free software:  you can redistribute it and/or modify
    it under the terms of the GNU Lesser General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Lesser General Public License for more details.

    You should have received a copy of the GNU Lesser General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
////////////////////////////////////////////////////////////////////////////////*/


namespace Morrow;

/**
* This class allows to work with events and hooks.
*
* There are many ways to build an event system with PHP (eg. by Observer, Decorator or Mediator pattern). 
* This class uses the Mediator pattern which is the simplest to understand and use.
* Basically, you have one global mediator (this class) that keeps track of your listeners.
* If you want to trigger an event, you send the event to the mediator.
* The mediator can then keep track of which listening objects want to receive that event, and pass the message along properly.
* 
* If you pass data with `trigger()` this can be used or ignored by the listeners.
* If the data is used and modified you have a hook system.
* If you do not return data within the listener the passed data will not be changed, so the other listeners get the original data.
* 
* Morrow itself throws the following events:
* 
* Event name | Passed as `$data` | Description
* -----------| ----------- | -----------
* `core.after_exception` | `object` The exception object | This event is triggered when the global exception handler had processed an exception. Useful to redirect the user to an error page.
* `core.after_view_creation` | `stream` The created content | This event is triggered when the \Morrow\View class has created the content source. Useful if you want to modify the created content before it is delivered to the client/browser. Or to write the content to a static HTML file. Or to...
*
* Example
* -------
* 
* First register your listener as early as possible in your code.
* ~~~{.php}
* // ... Default controller code
* 
* // register a listener for the event "foo"
* $this->event->on('foo', function($event, $data) {
*     return $data*2;
* });
* 
* // register a listener for the events "foo" and "bar"
* $this->event->on(array('foo', 'bar'), function($event, $data) {
*     return $data*2;
* });
*
* // ... Default controller code
* ~~~
*
* Now we have two listeners for the event `foo` and one for the event `bar`.
* If you trigger the events the passed data will be changed by the callbacks and returned.
* 
* ~~~{.php}
* // ... Controller code
*
* echo $this->event->trigger('foo', 1);
* echo $this->event->trigger('bar', 1);  
*
* // ... Controller code
* ~~~
*
* The result will be:
*
* ~~~{.text}
* 4
* 2
* ~~~
*/
class Event {
	/**
	 * All passed event callbacks.
	 * @var array $_events
	 */
	protected $_events = array();

	/**
	 * Registers a listener.
	 * @param  mixed $events A case insensitive string or an array of strings with event names.
	 * @param  callable $callback A callable object (like a closure or an array with object and method name ...) as defined here: http://php.net/manual/en/language.types.callable.php
	 * @return null
	 */
	public function on($events, Callable $callback) {
		if (!is_array($events)) $events = array($events);

		foreach ($events as $event) {
			$event = strtolower(trim($event));
			
			if (!isset($this->_events[$event])) $this->_events[$event] = array();
			$this->_events[$event][] = $callback;
		}
	}

	/**
	 * Triggers an event.
	 * @param  string  $event The case insensitive event name that should be triggered.
	 * @param  mixed $data The data that should be passed to one or many callbacks.
	 * @return mixed $data The data that was changed by one or many callbacks.
	 */
	public function trigger($event, $data = null) {
		$event = strtolower(trim($event));
		if (!isset($this->_events[$event])) return false;

		foreach ($this->_events[$event] as $callback) {
			// we don't want to affect the data if someone forgets to return the original data in his callback. 
			$result = call_user_func($callback, $event, $data);
			if ($result !== null) $data = $result;
		}

		return $data;
	}

	/**
	 * Returns all registered listeners. Useful for debugging.
	 * @return array All registered events.
	 */
	public function get() {
		return $this->_events;
	}
}