<?php

// This is the front controller used when executing the application in the
// development environment ('dev'). See:
//   * http://symfony.com/doc/current/cookbook/configuration/front_controllers_and_kernel.html
//   * http://symfony.com/doc/current/cookbook/configuration/environments.html

use Symfony\Component\Debug\Debug;
use Symfony\Component\HttpFoundation\Request;

// If you don't want to setup permissions the proper way, just uncomment the
// following PHP line. See:
// http://symfony.com/doc/current/book/installation.html#configuration-and-setup for more information
//umask(0000);

/**
 * @see http://snipplr.com/view/19069/
 * @param       $string
 * @param array $array
 * @return bool
 */
function wildcard_in_array ($string, $array = array ()) {
	foreach ($array as $value) {
		if (preg_match('/'.$string.'/', $value) !== false) {
			return true;
		}
	}
	return false;
}
$allowedIps = ['192.168.2.*', '127.0.0.1', 'fe80::1', '::1'];

// This check prevents access to debug front controllers that are deployed by
// accident to production servers. Feel free to remove this, extend it, or make
// something more sophisticated.
if (isset($_SERVER['HTTP_CLIENT_IP'])
    || isset($_SERVER['HTTP_X_FORWARDED_FOR'])
    || !(wildcard_in_array(@$_SERVER['REMOTE_ADDR'], $allowedIps) || php_sapi_name() === 'cli-server')
) {
    header('HTTP/1.0 403 Forbidden');
    exit('You are not allowed to access this file. Check '.basename(__FILE__).' for more information.');
}

/** @var Composer\Autoload\ClassLoader $loader */
$loader = require __DIR__.'/../app/autoload.php';
Debug::enable();

$kernel = new AppKernel('dev', true);
$kernel->loadClassCache();
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
