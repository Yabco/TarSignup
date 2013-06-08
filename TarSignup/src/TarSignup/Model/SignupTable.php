<?php
namespace TarSignup\Model;

use Zend\Db\TableGateway\TableGateway;

class SignupTable
{

    protected $tableGateway;

	public function __construct(TableGateway $tableGateway)
	{
	    $this->tableGateway = $tableGateway;
	}

	public function saveUser(Signup $signup)
	{
        $data = array(
            'name'     => $signup->name,
            'username' => $signup->username,
            'password' => $signup->password,
            'email'    => $signup->email,
            'hash'     => $signup->hash,
        );
        $this->tableGateway->insert($data);
	}
	
	public function getUserKey($hash)
	{
	    $rowset = $this->tableGateway->select(array('hash' => $hash));
	    $row = $rowset->current();
	    return $row;
	}
	
	public function activateUser($hash)
	{
	    $rowset = $this->tableGateway->select(array('hash' => $hash));
	    $row = $rowset->current();
	    $arr = (array) $row;
	    if ($row && empty($arr['active'])) {
	        $data = array(
        		'active'     => 1,
        		'userlevel'  => 3,
	        );
	        $this->tableGateway->update($data, array('hash' => $hash));
	    } else {
	        return FALSE;
	    }
	}
	
	public function checkUserExists($username)
	{
	   $rowset = $this->tableGateway->select(array('username' => $username));
	   $row = $rowset->current();
	   return ($row) ? FALSE : TRUE;
	}

	public function checkEmailExists($email)
	{
	   $rowset = $this->tableGateway->select(array('email' => $email));
	   $row = $rowset->current();
	   return ($row) ? FALSE : TRUE;
	}
}