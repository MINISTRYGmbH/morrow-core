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
 * Offers dummy data for developing purposes. Just German data at the moment.
 *
 * **Be careful:** It is possible that the email address is valid. If you know that you will send emails use the TLD example.com which is registered for such purposes.
 *
 * Example
 * -------
 *
 * ~~~{.php}
 * // Controller code
 *
 * $dummy_data = $this->dummy->get();
 * Debug::dump($dummy_data);
 *
 * // Controller code
 * ~~~
 */
class Dummy {
	/**
	 * Contains the salutations.
	 * @var array $salutation
	 */
	public $salutation  = array('female' => 'Frau', 'male' => 'Herr');

	/**
	 * Contains the possible firstnames.
	 * @var array $firstname
	 */
	public $firstname = array('female' => array('Hannah', 'Leoni', 'Lena', 'Anna', 'Lea', 'Lara', 'Mia', 'Laura', 'Lilly', 'Emily', 'Sarah', 'Emma', 'Neele', 'Marie', 'Sophie', 'Johanna', 'Julia', 'Maya', 'Lisa', 'Lina', 'Amelie', 'Alina', 'Leni', 'Sophia', 'Louisa', 'Paula', 'Clara', 'Angelina', 'Josephine', 'Charlotte', 'Jana', 'Chiara', 'Annika', 'Jule', 'Yasmin', 'Zoe', 'Finja', 'Pia', 'Katharina', 'Emilia', 'Fiona', 'Antonia', 'Victoria', 'Franziska', 'Vanessa', 'Celina', 'Emely', 'Melina', 'Isabel', 'Michelle'), 'male' => array('Leon', 'Lucas', 'Luca', 'Finn', 'Tim', 'Felix', 'Jonas', 'Louis', 'Maximilian', 'Julian', 'Max', 'Paul', 'Niclas', 'Jan', 'Ben', 'Elias', 'Yannik', 'Philip', 'Noah', 'Tom', 'Moritz', 'Nico', 'David', 'Nils', 'Simon', 'Fabian', 'Eric', 'Justin', 'Alexander', 'Jacob', 'Florian', 'Nick', 'Linus', 'Mika', 'Jason', 'Daniel', 'Lennard', 'Marvin', 'Yannis', 'Tobias', 'Dominic', 'Marlon', 'Marc', 'Johannes', 'Jonathan', 'Julius', 'Collin', 'Joel', 'Kevin', 'Vincent'),);

	/**
	 * Contains the possible lastnames.
	 * @var array $lastname
	 */
	public $lastname = array('Müller', 'Schmidt', 'Schneider', 'Fischer', 'Meyer', 'Weber', 'Becker', 'Wagner', 'Schulz', 'Herrmann', 'Schäfer', 'Bauer', 'Koch', 'Richter', 'Klein', 'Wolf', 'Schröder', 'Neumann', 'Zimmermann', 'Krüger', 'Hoffmann', 'Braun', 'Schmitz', 'Schmitt', 'Hartmann', 'Lange', 'Krause', 'Schmid', 'Werner', 'Schwarz', 'Meier', 'Lehmann', 'Köhler', 'Schulze', 'Maier', 'Walter', 'Huber', 'Mayer', 'Kaiser', 'Peters', 'Weiß', 'Möller', 'Peter', 'Frank', 'König', 'Sommer', 'Stein', 'Winter', 'Berger', 'Hansen');

	/**
	 * Contains the possible city names.
	 * @var array $city
	 */
	public $city = array('Berlin', 'Hamburg', 'München', 'Köln', 'Frankfurt am Main', 'Essen', 'Dortmund', 'Stuttgart', 'Düsseldorf', 'Bremen', 'Hannover', 'Duisburg',  'Leipzig', 'Nürnberg', 'Dresden', 'Bochum', 'Wuppertal', 'Bielefeld', 'Mannheim', 'Bonn', 'Münster');

	/**
	 * Contains the possible tlds.
	 * @var array $tld
	 */
	public $tld = array('de', 'com', 'info', 'net', 'org', 'ch', 'at');
	
	/**
	 * Returns dummy data for one dummy person.
	 * @param	string	$gender Configures the gender of your dummy person. Must be `male` or  `female`.
	 * @return	array	Returns an array with the keys `salutation`, `firstname`, `lastname`, `zip`, `city`, `email` and `nickname`.
	 */
	public function get($gender = null) {
		if (!isset($gender)) {
			$genders = array('male', 'female');
			$gender = $genders[ array_rand($genders) ];
		}
		
		$data['salutation'] = $this->getSalutation($gender);
		$data['firstname'] = $this->getFirstname($gender);
		$data['lastname'] = $this->getLastname();
		$data['zip'] = $this->getZip();
		$data['city'] = $this->GetCity();
		$data['email'] = $this->getEmail($data['firstname'], $data['lastname']);
		$data['nickname'] = $this->getNickname($data['firstname'], $data['lastname']);
		
		return $data;
	}

	/**
	 * Returns the salutation depending on the gender.
	 * @param	string	$gender Configures the gender of your dummy person. Must be `male` or  `female`.
	 * @return	string
	 */
	public function getSalutation($gender) {
		return $this->salutation[$gender];
	}
	
	/**
	 * Returns a firstname depending on the gender.
	 * @param	string	$gender Configures the gender of your dummy person. Must be `male` or  `female`.
	 * @return	string
	 */
	public function getFirstname($gender) {
		$count = count($this->firstname[$gender]);
		$nr = rand(0, $count-1);
		$data = $this->firstname[$gender][$nr];
		
		if (rand(1, 5) == 5) {
			$nr = rand(0, $count-1);
			$data .= '-'.$this->firstname[$gender][$nr];
		}
		
		return $data;
	}
	
	/**
	 * Returns a lastname depending on the gender.
	 * @return	string
	 */
	public function getLastname() {
		$count = count($this->lastname);
		$nr = rand(0, $count-1);
		$data = $this->lastname[$nr];
		
		if (rand(1, 5) == 5) {
			$nr = rand(0, $count-1);
			$data .= '-'.$this->lastname[$nr];
		}

		if (rand(0, 1)) $data = $this->_simplify($data);
			
		return $data;
	}
	
	/**
	 * Returns a city.
	 * @return	string
	 */
	public function getCity() {
		$count = count($this->city);
		$nr = rand(0, $count-1);
		$data = $this->city[ $nr ];
		if (rand(0, 1)) $data = $this->_simplify($data);

		return $data;
	}

	/**
	 * Returns a postal code. It is string and not a number because east german postal codes have a leading 0.
	 * @return	string
	 */
	public function getZip() {
		$data = rand(1000, 99999);
		$data = str_pad($data, 5, '0', STR_PAD_LEFT);
		return $data;
	}

	/**
	 * Returns a fake email address which is similar to the name of the person.
	 * @param	string	$firstname	The firstname of the dummy person.
	 * @param	string	$lastname	The lastname of the dummy person.
	 * @return	string
	 */
	public function getEmail($firstname, $lastname) {
		$data = $this->getNickname($firstname, $lastname);
		$data = strtolower($data);
		
		// create the the second part
		$count = count($this->tld);
		$nr = rand(0, $count-1);
		$host = $this->_getMnemonicValue().'.'.$this->tld[$nr];
		$data .= '@'.$host;
		
		$data = $this->_simplify($data);
		return $data;
	}
	
	/**
	 * Returns a fake nickname which is similar to the name of the person.
	 * @param	string	$firstname	The firstname of the dummy person.
	 * @param	string	$lastname	The lastname of the dummy person.
	 * @return	string
	 */
	public function getNickname($firstname, $lastname) {
		// create variations of names
		$variation[] = $firstname . '_' . rand(0, 99);
		$variation[] = $firstname . rand(0, 99);
		$variation[] = $firstname . '_' . rand(1960, 1995);
		$variation[] = $firstname . rand(1960, 1995);

		$variation[] = $lastname . '_' . rand(0, 99);
		$variation[] = $lastname . rand(0, 99);
		$variation[] = $lastname . '_' . rand(1960, 1995);
		$variation[] = $lastname . rand(1960, 1995);

		$variation[] = $firstname . '.' . $lastname;
		$variation[] = $firstname{0} . '.' . $lastname;
		$variation[] = $firstname . '.' . $lastname{0};
		
		$count = count($variation);
		$data = $variation[rand(0, $count-1)];
		return $data;
	}
	
	/**
	 * Changes german umlaute to their ascii representation for use in an email address.
	 * @param	string	$data
	 * @return	string
	 */
	protected function _simplify($data) {
		$replacements['in'] = array('ö', 'ä', 'ü', 'ß');
		$replacements['out'] = array('oe', 'ae', 'ue', 'ss');

		return str_replace($replacements['in'], $replacements['out'], $data);
	}
		
	/**
	 * Creates a mnemonic value to generate a dummy domain.
	 * @return	string
	 */
	protected function _getMnemonicValue() {
		$vowels = "aeiouy";
		$consonants = "bcdfghjklmnprstvwxz"; 
		for ($i=0; $i<4; $i++) {
			$password[] = $consonants[rand(0, strlen($consonants)-1)];
			$password[] = $vowels[rand(0, strlen($vowels)-1)];
		}
		return implode('', $password);
	}
}
