<?php
	require __DIR__.'/vendor/autoload.php';

	include "vendor/Adee2210/Common/src/GatewayCore.php";
	include "vendor/Adee2210/Common/src/GatewayTemplate.php";
	include "vendor/Adee2210/Netbanx/src/Gateway.php";
	include "vendor/Adee2210/Omnitest/src/Gateway.php";
	//include "vendor/Adee2210/Reliafund/src/Gateway.php";
	
	$app = new Silex\Application();
	$app->register(new Silex\Provider\SessionServiceProvider());
	$app->register(new Silex\Provider\TwigServiceProvider(), array(
		'twig.path' => __DIR__.'/views',
	));

	$app->get('/', function () use ($app) {
		$gateway = array();
		foreach(get_declared_classes() as $namespace)
			if(preg_match('/^Adee2210\\\\(?<name>[A-Za-z]+)\\\\Gateway$/',$namespace,$match)){
				if($gateobj = new $namespace)
					$gateway[strtolower($match['name'])] = $gateobj->getName();
			}
		return $app['twig']->render('gateway-list.twig', array(
			'gateways' => $gateway,
		));
	});

	$app->get('/{gateway}', function ($gateway) use ($app) {
		$gatename = implode("\\",array('Adee2210',ucfirst($gateway),'Gateway'));
		$gateobj = new $gatename;
		return $app['twig']->render('gateway-services.twig', array(
			'name' => $gateobj->getName(),
			'gateway' => $gateway,
			'services' => $gateobj->getConfig(),
		));
	});

	$app->get('/{gateway}/{service}', function ($gateway,$service) use ($app) {
	    $gatename = implode("\\",array('Adee2210',ucfirst($gateway),'Gateway'));
        $gateobj = new $gatename;
		$args = $gateobj->getConfig(array('run'=>$service));
        return $app['twig']->render('gateway-form.twig', array(
            'name' => $gateobj->getName(),
            'gateway' => $gateway,
            'service' => $service,
            'fields' => $args['args'],
            'optional' => $args['optional'],
			'vars' => $gateobj->getVars()['form']
        ));
    });

	$app->post('/{gateway}/{service}', function ($gateway,$service) use ($app) {
	    $gatename = implode("\\",array('Adee2210',ucfirst($gateway),'Gateway'));
        $gateobj = new $gatename;
		$args = $gateobj->getConfig(array('run'=>$service));
		$response = $gateobj->postForm($service,$app['request']->request->all());

        return $app['twig']->render('gateway-form.twig', array(
            'name' => $gateobj->getName(),
            'gateway' => $gateway,
            'service' => $service,
            'fields' => $args['args'],
            'optional' => $args['optional'],
			'vars' => $gateobj->getVars()['form'],
			'post' => $app['request']->request->all(),
			'request' => array('reponse'=>print_r($response,true),'send'=>$gateobj->send,'url'=>$gateobj->url),
        ));
    });

    $app->run();
?>