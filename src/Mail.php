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
* This class extends the widely adopted PHPMailer library (http://phpmailer.worxware.com/).
*
* You are able to change the behaviour of these methods with the following parameters you should set in your configuration files.
* These are all parameters which you would change as public members with the PHPMailer class.
* Take a look there for the description of the parameters.
*
* Type    | Keyname                | Default
* -----   | ---------              | ---------
* bool    | `mail.Mailer`          | `mail`
* bool    | `mail.From`            | `test@example.com`
* string  | `mail.FromName`        | `John Doe`
* integer | `mail.WordWrap`        | `0`
* string  | `mail.Encoding`        | `quoted-printable`
* string  | `mail.CharSet`         | `utf-8`
* boolean | `mail.SMTPAuth`        | `false`
* string  | `mail.Username`        | ``
* string  | `mail.password`        | ``
* string  | `mail.Host`            | ``
*
* Example
* -------
* 
* ~~~{.php}
* // controller code
* 
* // create text version of the mail
* $view = Factory::load('Views\Serpent:mail');
* $view->template = 'mail/welcome';
* $view->setContent('user', $user);
* $body = $view->getOutput();
* rewind($body);
* 
* // send mail
* $mail = Factory::load('Mail', $this->Config->get('mail'));
* $mail->Subject = 'Welcome new user';
* $mail->Body    = stream_get_contents($body);
* $mail->AddAddress($user['email']);
* $mail->Send(true);
*
* // controller code
* ~~~
*/
class Mail extends \PHPMailer {
	/**
	 * Initializes the class.
	 * @param  array $config A case insensitive string or an array of strings with event names.
	 * @return null
	 */
	public function __construct($config) {
		// set settings from config class
		if (isset($config) && is_array($config))
			foreach ($config as $key => $value) {
				$this -> $key = $config[$key];
			}
	}
	
	/**
	 * Registers a listener.
	 * @param  boolean $confirm A case insensitive string or an array of strings with event names.
	 * @return null
	 */
	public function Send($confirm = false) {
		// if From was not set ...
		if ($this->From == 'root@localhost') {
			throw new \Exception(__CLASS__.'<br />The key "From" could not be found in the assigned config, but has to be set.');
		}

		// Set sender to avoid to get marked as spam
		if (empty($this->Sender)) $this -> Sender		= $this -> From;
		
		// set user to standards for developing purposes
		if (isset($this->forceTo) && is_array($this->forceTo) && count($this->forceTo)>0 ) {
			$this->ClearAllRecipients();
			foreach ($this->forceTo as $email) {
				$this->AddAddress($email, 'Development User');
			}
		}
		
		// Send mail only if confirmed
		if ($confirm === true) $returner = parent::Send();
		else {
			$dump['from']    = $this->From;
			$dump['fromName']= $this->FromName;
			$dump['to']      = $this->to;
			$dump['cc']      = $this->cc;
			$dump['bcc']     = $this->bcc;
			$dump['subject'] = $this->Subject;
			$dump['body']    = $this->Body;
			$dump['altbody'] = $this->AltBody;
			dump($dump);
			$returner = true;
		}

		if ($returner === false) {
			throw new \Exception(__CLASS__.'<br />'.$this->ErrorInfo);
		} else {
			return true;
		}
	}
}
	
