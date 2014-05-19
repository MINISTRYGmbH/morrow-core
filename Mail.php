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

class Mail extends \PHPMailer {
	public function __construct($config) {
		// set settings from config class
		if (isset($config) && is_array($config))
			foreach ($config as $key => $value) {
				$this -> $key = $config[$key];
			}
	}
	
	// Mail-Template laden
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
	
