<?php
namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class RedirectingController
 * @package AppBundle\Controller
 * @see http://symfony.com/doc/current/routing/redirect_trailing_slash.html
 */
class RedirectingController extends Controller {
	public function removeTrailingSlashAction(Request $request) {
		$pathInfo = $request->getPathInfo();
		$requestUri = $request->getRequestUri();
		
		$url = str_replace($pathInfo, rtrim($pathInfo, ' /'), $requestUri);
		
		return $this->redirect($url, 301);
	}
}