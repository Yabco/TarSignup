<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/TarSignup for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace TarSignup;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\Mvc\ModuleRouteListener;
use TarSignup\Model\Signup;
use TarSignup\Model\SignupTable;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Adapter\DbTable as AuthDbTable;
use Zend\Crypt\Password\Bcrypt;
use Zend\Session\SaveHandler\DbTableGateway;
use Zend\Session\SaveHandler\DbTableGatewayOptions;
use Zend\Session\SessionManager;
use Zend\Authentication\Storage\Session;

class Module implements AutoloaderProviderInterface
{
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . str_replace('\\', '/' , __NAMESPACE__),
                ),
            ),
        );
    }

    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'TarSignup\Model\SignupTable' => function($sm) {
                    $tableGateway = $sm->get('SignupTableGateway');
                    $row          = new SignupTable($tableGateway);
                    return $row;
                },
                'SignupTableGateway' => function ($sm) {
                    $dbAdapter          = $sm->get('Zend\Db\Adapter\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new Signup());
                    return new TableGateway('users', $dbAdapter, null, $resultSetPrototype);
                },
                'AuthDbTable' => function($sm) {
                	$dbAdapter      = $sm->get('Zend\Db\Adapter\Adapter');
                	$authDbTable    = new AuthDbTable($dbAdapter);
                	return $authDbTable;
                },
                'SessionStorage' => function($sm) {
                    $sessionStorage = new Session();
                    return $sessionStorage;
                },
                'AuthService' => function($sm) {
                    $authService    = new AuthenticationService();
                    return $authService;
                },
                'SessionManager' => function($sm) {
                	$sessionManager   = new SessionManager();
                	return $sessionManager;
                },
                'Bcrypt' => function($sm) {
                	$bcrypt = new Bcrypt(array(
            			'salt' => 'randomvaluerandomvaluerandomvaluerandomvalue',
            			'cost' => 13,
                	));
                	return $bcrypt;
                },
                'SessionSaveHandler' => function($sm) {
                    $dbAdapter           = $sm->get('Zend\Db\Adapter\Adapter');
                    $sessionTableGateway = new TableGateway('session', $dbAdapter);
                    $sessionSaveHandler  = new DbTableGateway($sessionTableGateway, new DbTableGatewayOptions());
                    return $sessionSaveHandler;
                },
            ),
        );
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap($e)
    {
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
    }
}
