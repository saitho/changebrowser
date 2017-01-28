<?php
namespace AppBundle\Controller;

use AppBundle\Entity\ChangeContent;
use AppBundle\Entity\Project;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
	
	/**
	 * @param Request $request
	 * @return Response
	 * 
	 * @Route("/ajax_paths", name="ajaxpaths")
	 * @Method("GET")
	 * @Security("has_role('ROLE_ADMIN')")
	 */
	public function getPathsAction(Request $request) {
		$paths = [
			'ajax_loadProject: \''.$this->generateUrl('ajax_loadProject').'\'',
			'ajax_project_details: \''.$this->generateUrl('ajax_project_details').'\'',
			'ajax_project_add: \''.$this->generateUrl('ajax_project_add').'\'',
			'ajax_cli_fetchData: \''.$this->generateUrl('ajax_cli_fetchData').'\''
		];
		
		$js = 'var paths = {'.PHP_EOL;
		$js .= implode(','.PHP_EOL, $paths);
		$js .= '};'.PHP_EOL;
        		
		$response = new Response($js);
		$response->prepare($request);
		$response->setPublic();
		$response->setETag(md5($response->getContent()));
		$response->isNotModified($request);
		return $response;
	}
}
