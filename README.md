advEmail
========

Forked from geekMail to add extra features
A standalone version of the CodeIgniter PHP Framework's Email library

- Version 1.0


This class is a standalone version of the Email library used in the CodeIgniter PHP Framework:

- http://codeigniter.com/


Most of the existing CodeIgniter documentation for the library should work, with the exception
that the library won't be dynamically loaded from a CodeIgniter controller (`$this->load->...` and
"=`$this->email->from...`):

- http://codeigniter.com/user_guide/libraries/email.html


Usage example:
--------------
```php
<?php

require 'advEmail.php';

$advEmail = new advEmail();

$advEmail->setMailType('html');

$advEmail->from('noreply@mattclements.co.uk', 'Matt Clements');

$advEmail->to('matt@mattclements.co.uk');
//$advEmail->cc('matt@mattclements.co.uk');
//$advEmail->bcc('matt@mattclements.co.uk');

$advEmail->subject('Example subject');

$advEmail->message('Example message.');
$advEmail->set_alt_message('Text alternative email - manually set');

$advEmail->attach('/home/matt/file1.txt');
$advEmail->attach('/home/matt/file2.zip');

if (!$advEmail->send())
{
	$errors = $advEmail->getDebugger();
	print_r($errors);
}
```


Adapted by: Matt Clements
matt@mattclements.co.uk
http://www.mattclements.co.uk

GeekMail developed by: Willem van Zyl
willem@geekology.co.za
http://www.geekology.co.za/blog/


---
As with CodeIgniter itself, this code is free and Open Source, but
EllisLab, Inc. (the creators of CodeIgniter) require that this license
agreement be included in all adaptations:

http://codeigniter.com/user_guide/license.html
---