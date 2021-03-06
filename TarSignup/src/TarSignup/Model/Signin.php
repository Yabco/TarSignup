<?php
namespace TarSignup\Model;

use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

class Signin implements InputFilterAwareInterface
{
    public $username;
    public $password;
    public $salt;
    public $active;

    protected $inputFilter;

    public function exchangeArray($data)
    {
    	$this->username     = (isset($data['username']))   ? $data['username']                         : NULL;
    	$this->password     = (isset($data['password']))   ? $data['password']                         : NULL;
    	$this->salt         = (isset($data['salt']))       ? $data['salt']                             : NULL;
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
    		$this->inputFilter = $inputFilter;
    	}
    	return $this->inputFilter;
    }
}