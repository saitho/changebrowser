<?php
namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * @Route("/")
 */
class HomeController extends Controller {
    /**
     * @Route("/", name="homepage")
     * @Method("GET")
     * @Cache(smaxage="10")
     */
    public function indexAction($page) {
        return $this->render('default/homepage.html.twig', []);
    }
}
