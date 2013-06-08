<?php
namespace TarSignup\Model;

use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;
use Zend\Crypt\Password\Bcrypt;

class Signup implements InputFilterAwareInterface
{
    public $name;
    public $username;
    public $password;
    public $salt;
    public $email;
    public $hash;
    public $active;

    protected $inputFilter;

    private $_bcrypt;

    public function __construct()
    {
        $this->_bcrypt = new Bcrypt(array(
            'salt' => 'randomvaluerandomvaluerandomvaluerandomvalue',
            'cost' => 13,
        ));
    }

    public function exchangeArray($data)
    {
    	$this->name         = (isset($data['name']))       ? ucfirst($data['name'])                    : NULL;
    	$this->username     = (isset($data['username']))   ? $data['username']                         : NULL;
    	$this->password     = (isset($data['password']))   ? $this->_bcrypt->create($data['password']) : NULL;
    	$this->salt         = (isset($data['salt']))       ? $data['salt']                             : NULL;
    	$this->email        = (isset($data['email']))      ? $data['email']                            : NULL;
    	$this->hash         = (isset($data['security']))   ? $data['security']                         : NULL;
    	$this->active       = (isset($data['active']))     ? $data['active']                           : NULL;
    }

    public function setInputFilter(InputFilterInterface $inputFilter)
    {
    	throw new \Exception("Not used");
    }

    public function getInputFilter()
    {
    	if (!$this->inputFilter) {
    		$inputFilter = new InputFilter();
    		$factory     = new InputFactory();
    		$inputFilter->add($factory->createInput(array(
				'name' => 'name',
				'requiered' => TRUE,
				'filters' => array(
					array('name' => 'StripTags'),
					array('name' => 'StringTrim'),
				),
				'validators' => array(
					array(
						'name'    => 'StringLength',
						'options' => array(
							'encoding' => 'UTF-8',
							'min'      => 3,
							'max'      => 30,
						),
					),
				    /*** XAMPP ERROR Zend\I18n\Filter component requires the intl PHP extension ***/
				    /*
					 array(
    				   'name'    => 'Alpha',
	    				'options' => array(
	    						'allowWhiteSpace' => FALSE,
	    				),
			        ),
			        */
				),
    		)));
    		$inputFilter->add($factory->createInput(array(
				'name' => 'username',
				'required' => TRUE,
				'filters' => array(
					array('name' => 'StripTags'),
					array('name' => 'StringTrim'),
				),
				'validators' => array(
					array(
						'name'    => 'StringLength',
						'options' => array(
							'encoding' => 'UTF-8',
							'min'      => 3,
							'max'      => 30,
						),
					),
				),
    		)));
    		$inputFilter->add($factory->createInput(array(
				'name' => 'password',
				'required' => TRUE,
				'filters' => array(
						array('name' => 'StringTrim'),
				),
				'validators' => array(
					array(
						'name'    => 'StringLength',
						'options' => array(
							'encoding' => 'UTF-8',
							'min'      => 3,
							'max'      => 50,
						),
					),
				),
    		)));
    		$inputFilter->add($factory->createInput(array(
				'name' => 'repassword',
				'required' => TRUE,
				'filters' => array(
					array('name' => 'StringTrim'),
				),
				'validators' => array(
					array(
						'name'    => 'StringLength',
						'options' => array(
							'encoding' => 'UTF-8',
							'min'      => 3,
							'max'      => 50,
						),
					),
					array(
						'name' => 'identical',
						'options' => array(
							'token' => 'password',
							'messages' => array(\Zend\Validator\Identical::NOT_SAME => 'Please retype the same password.'),
						),
					),
				),
    		)));
    		$inputFilter->add($factory->createInput(array(
				'name' => 'email',
				'required' => TRUE,
				'filters' => array(
					array('name' => 'StringTrim'),
				),
				'validators' => array(
					array(
						'name'    => 'StringLength',
						'options' => array(
							'encoding' => 'UTF-8',
							'min'      => 3,
							'max'      => 100,
						),
					),
					array(
						'name' => 'EmailAddress',
						'options' => array(
							'useMxCheck' => FALSE
						),
					),
				),
    		)));
    		$inputFilter->add($factory->createInput(array(
				'name' => 'reemail',
				'required' => TRUE,
				'filters' => array(
					array('name' => 'StringTrim'),
				),
				'validators' => array(
					array(
						'name'    => 'StringLength',
						'options' => array(
							'encoding' => 'UTF-8',
							'min'      => 3,
							'max'      => 100,
						),
					),
					array(
						'name' => 'EmailAddress',
						'options' => array(
							'useMxCheck' => FALSE
						),
					),
					array(
						'name' => 'identical',
						'options' => array(
							'token' => 'email',
						),
					),
					array(
						'name' => 'identical',
						'options' => array(
    	    				'token' => 'email',
    	    				'messages' => array(\Zend\Validator\Identical::NOT_SAME => 'Please retype the same email address.'),
						),
					),
				),
    		)));
    		$this->inputFilter = $inputFilter;
    	}
    	return $this->inputFilter;
    }
}