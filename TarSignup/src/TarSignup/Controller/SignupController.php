<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/TarSignup for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace TarSignup\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use TarSignup\Form\RegisterForm;
use TarSignup\Form\LoginForm;
use TarSignup\Model\Signup;
use TarSignup\Model\Signin;
use Zend\Mail\Message;
use Zend\Mail\Transport\Sendmail as SendmailTransport;
use Zend\Session\Config\SessionConfig;

class SignupController extends AbstractActionController
{
    protected $signupTable;
    protected $authService;
    protected $authDbTable;
    protected $sessionStorage;
    protected $bcrypt;
    protected $sessionSaveHandler;
    protected $sessionManager;

    public function profileAction()
    {
        $key = $this->params()->fromRoute('key');
        if ($key && $this->getAuthService()->hasIdentity() && $key === $this->getAuthService()->getIdentity()) {
            return new ViewModel(array(
        		'scc'  => $this->flashMessenger()->getCurrentSuccessMessages(),
        		'err'  => $this->flashMessenger()->getCurrentErrorMessages(),
                'user' => $this->getAuthService()->getIdentity(),
            ));
        } else {
            $this->flashMessenger()->addErrorMessage('Session expired or not valid.');
            return $this->redirect()->toRoute('home');
        }

    }

    public function logoutAction()
    {
    	if ($this->getAuthService()->hasIdentity()) {
    		$this->getSessionManager()->forgetMe();
    		$this->getAuthService()->clearIdentity();
    		$this->flashMessenger()->addSuccessMessage("You've been logged out");
    	}
    	return $this->redirect()->toRoute('home');
    }

    public function loginAction()
    {
        $this->_checkIfUserIsLoggedIn();
        $key = $this->params()->fromRoute('key');
        if ($key == 'success') {
        	$this->flashMessenger()->addSuccessMessage('Your account is fully activated.');
        }
        $form = new LoginForm();
        $request = $this->getRequest();
        if ($request->isPost()) {
            $signin = new Signin();
            $form->setInputFilter($signin->getInputFilter());
            $form->setData($request->getPost());
            if ($form->isValid()) {
                $signin->exchangeArray($form->getData());
                $arrData = $form->getData();
                $this->getAuthDbTable()
                        ->setTableName('users')
                        ->setIdentityColumn('username')
                        ->setCredentialColumn('password')
                        ->setIdentity($arrData['username'])
                        ->setCredential($this->getBcrypt()->create($arrData['password']))
                ;
                $this->getAuthService()
                        ->setAdapter($this->getAuthDbTable())
                        ->setStorage($this->getSessionStorage())
                ;
                $result = $this->getAuthService()->authenticate();
                foreach($result->getMessages() as $message) {
                	$this->flashMessenger()->addErrorMessage($message);
                }
                if ($result->isValid()) {
                    if ($arrData['remember'] == 1 ) {
                        $this->getSessionManager()->rememberMe(100000);
                    }
                    $resultRow = (array) $this->getAuthDbTable()->getResultRowObject(array('id', 'username'));

                    $this->getSessionStorage()->write($resultRow['username']);

                    /*** START SESSION DB STORAGE ***/

                    $sessionSaveHandler = $this->getSessionSaveHandler();
                    $manager     = $this->getSessionManager();
                    $sessionConfig = new SessionConfig();
                    $sessionSaveHandler->open($sessionConfig->getOption('save_path'), 'storage_namespace');
                    $manager->setSaveHandler($sessionSaveHandler);

                    /*** END SESSION DB STORAGE ***/

                    return $this->redirect()->toRoute('tar-signup', array(
                    		'action' => 'profile',
                    		'key'    => $this->getAuthService()->getIdentity(),
                    ));
                }
            } else {
                $this->flashMessenger()->addErrorMessage('Form data error');
                return $this->redirect()->toRoute('home');
            }
        }
        return new ViewModel(array(
            'form' => $form,
            'scc'  => $this->flashMessenger()->getCurrentSuccessMessages(),
            'err'  => $this->flashMessenger()->getCurrentErrorMessages(),
        ));
    }

    public function activateAction()
    {
        $this->_checkIfUserIsLoggedIn();
        $key = $this->params()->fromRoute('key');
        if ($key) {
            $userData = $this->getSignupTable()->activateUser($key);
            if($userData !== FALSE) {
                return $this->redirect()->toRoute('tar-signup', array(
            		'action' => 'login',
                    'key'    => 'success',
                ));
            } else {
                $this->flashMessenger()->addErrorMessage('No match found or user already activated.');
                return $this->redirect()->toRoute('home');
            }
        } else {
            $this->flashMessenger()->addErrorMessage('Sorry! Activation failed. Please try again later.');
            return $this->redirect()->toRoute('home');
        }
    }

    public function noticeAction()
    {
        $this->_checkIfUserIsLoggedIn();
    	return new ViewModel();
    }

    public function registerAction()
    {
        $this->_checkIfUserIsLoggedIn();
        $form = new RegisterForm();
        $request = $this->getRequest();
        if ($request->isPost()) {
        	$signup = new Signup();
        	$form->setInputFilter($signup->getInputFilter());
        	$form->setData($request->getPost());
        	if ($form->isValid()) {
        		$signup->exchangeArray($form->getData());
        		$arrData = $form->getData();
        		$this->getSignupTable()->checkUserExists($arrData['username']) ?
        		      TRUE : $this->flashMessenger()->addErrorMessage('Username already exists');
        		$this->getSignupTable()->checkEmailExists($arrData['email']) ?
        		      TRUE : $this->flashMessenger()->addErrorMessage('Email already exists');
        		if (!$this->flashMessenger()->hasCurrentErrorMessages()) {

        		    /*** Start Mail Configuration ***/
        		    $addTo        = $arrData['email'];
        		    $addFrom      = 'register@bisoncargo.com';
        		    $setSubject   = 'Your Registration is Pending Approval';
        		    $hyperlink    = '<a href="http://localhost/zend/xtest/public/signup/activate/' . $arrData['security'] . '">Click Here To Activate Your Account</a>';
        		    $setBody      =
                    'Greetings ' . ucfirst($arrData['name']) .
                    ',<br />
                    Thank you for applying for registration with us. We have
                    received your request and we will process it as soon as you
                    confirm your email address by clicking on the following
                    hyperlink:
                    <br />' .
                    $hyperlink;
        		    /*** End Mail Configuration ***/

        		    $message = new Message();
        		    $message->setEncoding("UTF-8");
        		    $message->toString();
        		    $message->addTo($addTo)
                		    ->addFrom($addFrom)
                		    ->setSubject($setSubject)
                		    ->setBody($setBody);
        		    $transport = new SendmailTransport();

        		    /*** In localhost throw error ***/

        		    /*
        		    if ($transport->send($message)) {
            		    $this->getSignupTable()->saveUser($signup);
        		    }
        		    */

        		    return $this->redirect()->toRoute('tar-signup', array(
                		'action' => 'notice'
                    ));
        		}
        	}
        }
        return new ViewModel(array(
            'form' => $form,
            'err'  => $this->flashMessenger()->getCurrentErrorMessages(),
        ));
    }

    public function getSessionManager()
    {
        if (!$this->sessionManager) {
        	$sm = $this->getServiceLocator();
        	$this->sessionManager = $sm->get('SessionManager');
        }
        return $this->sessionManager;
	}

    public function getSessionSaveHandler()
    {
        if (!$this->sessionSaveHandler) {
        	$sm = $this->getServiceLocator();
        	$this->sessionSaveHandler = $sm->get('SessionSaveHandler');
        }
        return $this->sessionSaveHandler;
	}

    public function getSignupTable()
    {
        if (!$this->signupTable) {
        	$sm = $this->getServiceLocator();
        	$this->signupTable = $sm->get('TarSignup\Model\SignupTable');
        }
        return $this->signupTable;
	}

	public function getAuthService()
	{
		if (!$this->authService) {
			$sm = $this->getServiceLocator();
			$this->authService = $sm->get('AuthService');
		}
		return $this->authService;
	}

	public function getAuthDbTable()
	{
		if (!$this->authDbTable) {
			$sm = $this->getServiceLocator();
			$this->authDbTable = $sm->get('AuthDbTable');
		}
		return $this->authDbTable;
	}

	public function getSessionStorage()
	{
		if (!$this->sessionStorage) {
			$sm = $this->getServiceLocator();
			$this->sessionStorage = $sm->get('SessionStorage');
		}
		return $this->sessionStorage;
	}

	public function getBcrypt()
	{
		if (!$this->bcrypt) {
			$sm = $this->getServiceLocator();
			$this->bcrypt = $sm->get('Bcrypt');
		}
		return $this->bcrypt;
	}

	final private function _checkIfUserIsLoggedIn()
	{
        if ($this->getAuthService()->hasIdentity()) {
        	return $this->redirect()->toRoute('tar-signup', array(
        		'action' => 'profile',
                'key'    => $this->getAuthService()->getIdentity(),
            ));
        }
	}

	public function __destruct()
	{
	    $this->flashMessenger()->clearMessagesFromContainer();
	}
}
