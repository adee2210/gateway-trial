<?php
	require __DIR__.'/vendor/autoload.php';
	
	$app = new Silex\Application();
	$app->register(new Silex\Provider\SessionServiceProvider());
	$app->register(new Silex\Provider\TwigServiceProvider(), array(
		'twig.path' => __DIR__.'/views',
	));

	$app->get('/', function () use ($app) {
		$gateway = array();
		$packages = json_decode(file_get_contents(__DIR__.'/composer.json'));
		foreach($packages->require as $package => $version)
			if(preg_match('/^adee2210\/(?<name>[a-z]+)$/',$package,$match))
				if($match['name']!='common') {
					$gateway[$match['name']] = ucfirst($match['name']);
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
