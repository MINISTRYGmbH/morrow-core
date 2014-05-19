Form Handling
=============

The form class simplifies the handling and validation of user input. It can also make creating HTML forms simpler by taking care of correct naming, refilling field values and showing errors. It also includes a work-around, so that uploaded files are not lost when an user fills out incorrectly a field.

In order to do all this, a certain amount of abstraction and definition is necessary. The Form.class does not concern itself with the appearance of fields, only whether they contain multiple choices or are simple fields. A definition will further contain information about requirements and validation functions as well as default values. This is done by a combination of definition files and Morrow's i18n-handling. The first part of this page is about defining form elements.

The appearance of form elements is up to the template itself, or some other input medium, for example a flash film. If you are using HTML there is an extra class "FormHTML" which works with the Form.class to produce valid input fields based on your definitions, fill in the values and show errors. For Smarty there are plugins, that use the FormHtml class. These are discussed on the second part of this page.

Finally, the end of this page shows an example of how to control the flow of a typical form submission process.


Form Definition
----------------

A definition array is created in the _forms folder with the name of the alias, so that the Form.class knows which fields, or "elements" to work with and what to do with them. Conforming to Morrow conventions, multiligual contents are separated to _i18n.

~~~
_forms/myformpage.php    # Definition
_i18n/en/myformpage.php  # labels and options for English
~~~

### Structure

~~~{.php}
// ... Form definition
$elements['myform'] = array(
    "name" => array(
        #...
    ),
    "email" => array(
        #...
    ),
);
~~~

The array $elements contains keys that represent the names of forms. A similar array is used when submitting user input to the Form class. In this way, the Form.class can determine which form has been submitted and which values belong to a particular form. It is possible to define multiple forms per page.

In the example above, the form "myform" is defined containing two elements: "name" and "email", according to the keys on the next array level. The following level defines each element.

### Simple Elements

~~~{.php}
// ... Form definition
$elements['myform'] = array(
    "name" => array(
        "type" => "simple",
        "required" => false,
        "default" => "5",
    ),
    "email" => array(
        "type" => "simple",
        "required" => true,
        "checktype" => "Email",
    ),
    "comment" => array(
        #is automatically "simple" and not "required"
    ),
);
~~~

Each element has a type. Unlike HTML, the Form class is not concerned with the appearance of input fields. Therefore there are only two types that can be defined: "simple" or "set". Simple elements offer no choices to the user an can contain only one value. Sets on the other hand can offer the user multiple values to choose from. An field for an e-mail address or a single check box for Newsletter subscription would be examples of "simple" fields. A selector for user groups would be a "set". The default type is alway "simple".

No matter what type an element has, the following attributes can always be set:

* required: the user must submit input (Default is false)
* default: the value a field has if the user does not change it.
* checktype: the name of the method to call for more complex validation (see Validator class)
* compare: the name of another element, whose value should be also sent to the checktype-method (in order to compare). Requires a that a method is set in for checktype.
* arguments: mixed (for checktype validation function) 

"Simple" elements can also have the following:

* example: a value that can be display to the user, but does not count as input. 

**i18n**

Some attributes of form elements are language dependent. For example almost all elements have labels. According to the conventions of MorrowTwo, language dependent content is defined in files under ''i18n. Form definitions have their own array $form and the basic structure is the same as that of the $elements array.

~~~{.php}
$form['testform']['name']['label'] = "Zahl";
$form['testform']['email']['label'] = "eMail-Adresse";
~~~

Since it is possible that the attributes "default" and "example" contain language specific contents, they both may be set either in _form or in _i18n.

~~~{.php} 
$form['testform']['number']['label'] = "Number";
$form['testform']['number']['default'] = "10";

$form['testform']['email']['label'] = "eMail-Adresse";
$form['testform']['email']['example'] = "your@email-address.com";
~~~

The definition for "default" or "example" in _i18n takes precedence and will replace any value set in form.

Examples of "simple" elements would be:

* hidden
* text
* password
* checkbox
* file
* "date"
* Sets 

### Sets

~~~{.php}
// ... Form definition
$elements['myform'] = array(
    "weekdays" => array(
        "type" => "set",
         "multiple" => false,
         "options" => array(
             "weekday_1",
             "weekday_2",
             "weekday_3",
             "weekday_4",
             "weekday_5",
             "weekday_6",
             "weekday_0",
         ),
         "default" => "weekday_2",
     ),
);
~~~

"Sets" are elements that contain a pre-defined selection of values. The user may choose one or more of these values, may not, however, enter any value that is not contained in the list.

Where an user may choose multiple values or only one is determined by the attribute "multiple". The default value of "multiple" is false.

The values of the choices are set using the "options" array. These are the values that are submited as form input.

~~~{.php}
// ... form definition
$elements['myform'] = array(
    "weekdays" => array(
        "type" => "set",
        "multiple" => true,
        "options" => array(
            "weekday_1",
            "weekday_2",
            "weekday_3",
            "weekday_4",
            "weekday_5",
            "weekday_6",
            "weekday_0",
        ),
        "default" => array("weekday_6","weekday_0"),
    ),
);
~~~

"Multiple-Sets" (sets with attribute multiple=true) only differ from other sets in that the defaut attribute may be an array of values. That way, more than one value can be "pre-selected". Examples of "Sets"

* select
* radios
* "check-group" (multiple)
* select (multiple) 

**Sets and _i18n**

Just like labels, the way options are presented to the user is usually language specific. Therefore, parallel to the options array, there is an "output" array in _i18n definition.

~~~{.php} 
$form['testform']['weekdays']['label'] = "Week days";
$form['testform']['weekdays']['output'] = array("Mon","Tue","Wed","Thu","Fri","Sat","Sun");
~~~


Validator class
---------------

The Form class checks whether required fields have been filled during validation. For more complex validations, methods from the Validator class can be used or the developer can create methods in a new class that extends the Validator class. The Form class is informed of the validator class by the method setValidator(String classname).

Once a method exists, the name of the Function is added as a "checktype" attribute to the element definition.

Validator must follow a few conventions.


### Naming

The name of the method must start with "check". This "check" is, however, not to be used when defining the attribute "checktype". For example the method checkPassword would be assigned to an element as checktype="Password".

~~~
bool checkPassword( mixed $value, string & $error, [string $compare_value], [mixed $arguments], [array $locale_values]  )
~~~

### Arguments

* $value: the Form.class passes the value of the field
* &$error: the error key, which is used by the Form.class and therefore passed by reference.
* $compare_value: the value of another field that has been defined in the "compare" attribute (optional).
* $arguments: programmer defined arguments that are passed from the form-definition to the validator-method
* $locale_values: the config array from the i18n-file for the current language, that could be useful for validation functions. 

Methods must return true, if validating was successful, or false, if the input was not correct. If validation fails, you must set $error. The values are "keys" to language dependent definitions. See Error Messages below.

### Example

"email" => array(
    "type" => "simple",
    "required" => true,
    "checktype" => "Email",
);

During validation the Form class calls the method !checkEmail in Validator class and passes the value the user submitted for "email".

~~~{.php}
public static function checkEmail($value, &$error, $compare_value = null) {
    $localpart = "[a-z0-9!#$%&'*+-/\=?^_`{|}~]"; // RFC 2822
    $domainpart = "[\.a-z0-9-]";
    if (!preg_match("=^$localpart+(\.$localpart+)*@($domainpart+)$=i", $value, $match)){
        $error = 'BADEMAIL';
        return false;
    }
    return true;
}
~~~

In the example code above, the value is checked against a regular expresion to determine whether it meets certain criteria. If it does not, the method sets $error to 'BADEMAIL' and returns false. Otherwise it returns true.

A further example shows the use of the "compare" attribute.

~~~{.php}
"password" => array(
    "required" => true,
    "checktype" => "Password",
    "compare" => "password2",
),
"password2" => array(
    "required" => true,
),
~~~

~~~{.php}
public static function checkPassword($value, &$error, $compare_value=null) {
    if($compare_value !== null && $value != $compare_value){
        $error = 'MISMATCH';
        return false;
    }
    return true;
}
~~~


Error Messages
--------------

Since error messages are generally specific to different languages, only keys should be used to define errors. The Form.class sets the key "MISSING" if a required element has no value submitted. The keys set in Validator.class are up to the developer to define. These keys should then be translated into real language in the i18n files. Typically this is done in the global.php file for each language, since the keys can usually be used for more than one form or page.

~~~{.php}
$form['errors'] = array(
    'MISSING' => "Please enter a value.",
    'BADEMAIL' => "Please check your address.",
    'MISMATCH' => "Your repeated password was not identical.",
);
~~~


Generating HTML
---------------

### FormHtml class

to ease the process of generating HTML the FormHtml class has been developed, which works directly with the Form class. For a list of methods and PHP-Examples see FormHtml in the "User Classes" section. If you are using the serpent view class the following section should be useful.

### Serpent Functions

There are several serpent functions that use the FormHtml class to generate the elements of a form in your template. For flexibility of design, the tags for "labels", "elements" and "errors" are independent and can be placed any where in the html code.

You will have to take care of the submit button and its internationalization, yourself though.

**Example**

~~~{.html}
<form action="~~:url($page.path)~" method="post">
        ~~FormHTML::getLabel('myform', 'email', array('errorclass' => 'error'));~:
        ~~FormHTML::getElement('myform', 'email', array('errorclass' => 'error'));~<br /><br />
    <input type="submit" name="submit" value="SEND" />
</form>
~~~

results in

~~~{.html}
<form action="path/to/current/page/" method="post">   
    <label for="mw_myform_email" class=" " >E-Mail</label>:
    <input id="mw_myform_email" type="text" name="myform[email]" class=" " /><br /><br />
     <input type="submit" name="submit" value="SEND" />
</form>
~~~


Form control
------------

Normally the only functionality needed is to check whether a form has been submitted, validate the input and then do something with it (send it per e-mail or save it in a database). The following code is an example of simple form handling.

**Example**

~~~{.php}
$this->form->setInput($this->input->get());
 
if($this -> form -> isSubmitted('myform')) {
    $valid = $this -> form -> validate('myform');
    if ($valid) {
        $formdata = $this -> form -> getValues('myform');
        
        # send mail, save in database, etc.
        # show other contents, or redirect

        $this->url->redirect('success/');
    } else {
        # There was an error, do nothing (allow FormHTML to set the appropriate error-classes)
    }
}
~~~

The Form class expects all forms to have their own array of inputs. The key is the name of the form (as seen in the form definition). The easiest way to do this is to use PHP's built in array handling for input fields. In an HTML-Form that would look like this:

~~~{.html}
<input type="text" name="formname[elementname]" value="" />
~~~

The FormHtml class mentioned above does this automatically.

For a complete list of methods, see the User-Class definition for FormHtml?. 
