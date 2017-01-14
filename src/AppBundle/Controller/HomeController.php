<?php
namespace AppBundle\Controller;

use AppBundle\Entity\ChangeContent;
use AppBundle\Entity\Project;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * @Route("/")
 */
class HomeController extends Controller {
    /**
     * @Route("/", name="homepage")
     * @Method("GET")
	 * @Security("has_role('ROLE_ADMIN')")
     */
    public function indexAction() {
		$entityManager = $this->getDoctrine()->getManager();
		$projects = $entityManager->getRepository(Project::class)->findBy([], ['title' => 'ASC']);
        return $this->render('home/home.html.twig', ['projects' => $projects]);
    }
}
