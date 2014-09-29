Form Handling
=============

Form handling is one of the most exhausting actions you have to do.
The \Morrow\Form class makes creating HTML forms simpler by taking care of correct naming, refilling field values and showing errors.
For validating the form data you will use it together with the \Morrow\Validator class which validates the user data.

This is an commented controller example for a form with only three fields:

~~~{.php}
<?php
// ... Controller code

// we define salutations we want to validate (it has to be one of the keys of this array)
// and we pass the salutations to the template so we can use them with the Form class to output the HTML
$salutations = array('salutations' => array('mr' => 'Mister', 'mrs' => 'Misses'));
$this->view->setContent('salutations', $salutations);

// these are the rules for the validator class. the input data has to fulfill those requirements
$rules =  array(
	'salutation'	=> array('required', 'array' => array('in_keys' => $salutations)),
	'firstname'		=> array('required'),
	'lastname'		=> array('required'),
);

// we get the user input
$input  = $this->input->get();

// the array which will contain the errors after validating
$errors = array();

// we need a hint to notice the form was submitted. here we use the value "sent" from the submit button
if (isset($input['sent'])) {
	// now let's validate the data
	// on success $data will only contain values that exist in the $rules array
	if ($data = $this->validator->filter($input, $rules, $errors, true)) {
		// ok, data was valid
		Debug::dump($data);
	} else {
		// data was not valid
		Debug::dump($errors);
	}
} else {
	// if the form has not been submitted we could set default values, e.g. for the salutation
	$input['salutation'] = 'mrs';
}

// we pass an instance of the form class to the template.
// the form class need the user input and the errors of the validation process as constructor parameters
$form = Factory::load('Form', $input, $errors);
$this->view->setContent('form', $form);

// ... Controller code
?>
~~~

OK, now he have setup our controller. Let's take a look at the template.

~~~{.php}
<!-- first we -->
<?php
// set the error class the form class will append to labels and input fields in the case of errors
// this is optional, the default class is "error"
\Morrow\Form::$error_class = 'error';
?>

<!-- we want the input fields to have a red background if an error occurs -->
<style>
input.error { background: #fee; }
</style>

<form action="~~:raw($page.path.absolute)~">
	~~$form->label('salutation', 'Salutation')~
	~~$form->select('salutation', :raw($salutations))~
	~~$form->error('salutation')~
	<br />

	~~$form->label('firstname', 'First name')~
	~~$form->text('firstname')~
	~~$form->error('firstname')~
	<br />

	~~$form->label('lastname', 'Last name')~
	~~$form->text('lastname')~
	~~$form->error('lastname')~
	<br />

	<input type="submit" name="sent" value="Submit" />
</form>
~~~
