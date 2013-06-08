<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'TarSignup\Controller\Signup' => 'TarSignup\Controller\SignupController',
        ),
    ),
    'router' => array(
		'routes' => array(
			'tar-signup' => array(
				'type'    => 'segment',
				'options' => array(
					'route'    => '/signup[/:action][/:key]',
					'constraints' => array(
						'action'  => '[a-zA-Z][a-zA-Z0-9_-]*',
						'key'     => '[a-zA-Z0-9_-]*',
					),
					'defaults' => array(
                        '__NAMESPACE__' => 'TarSignup\Controller',
                        'controller'    => 'Signup',
                        'action'        => 'register',
                    ),
				),
			),
		),
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            'TarSignup' => __DIR__ . '/../view',
        ),
    ),
);
