<?php
namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * @Route("/home")
 */
class HomeController extends Controller {
    /**
     * @Route("/", name="home")
     * @Method("GET")
     */
    public function indexAction() {
        return $this->render('home/home.html.twig');
    }
}
