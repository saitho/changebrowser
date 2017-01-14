<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Change;
use AppBundle\Entity\Project;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/ajax")
 * @Security("has_role('ROLE_ADMIN')")
 */
class AjaxController extends Controller {
	/**
	 * @Route("/loadProject", name="ajax_loadProject")
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function loadProjectAction(Request $request){
		$response = ['status' => false, 'message' => ''];
		$project_id = $request->request->get('project_id');
		if(empty($project_id)) {
			$response['message'] = $this->get('translator')->trans('Missing Project ID');
			return new Response(json_encode($response));
		}
		$projectRepo = $this->getDoctrine()->getRepository(Project::class);
		/** @var Project $project */
		$project = $projectRepo->find($project_id);
		if(empty($project)) {
			$response['message'] = $this->get('translator')->trans('Project not found');
			return new Response(json_encode($response));
		}
		$changes = [];
		/** @var Change $change */
		foreach($project->getChanges() AS $change) {
			$changes[] = [
				'id' => $change->getId(),
				'author' => $change->getAuthor(),
				'date' => $change->getDate(),
				'title' => $change->getTitle(),
				'type' => $change->getType(),
				'CSSClassForType' => $change->getCSSClassForType(),
			];
		}
		$response = ['status' => true, 'changes' => $changes];
		//handle data
		return new Response(json_encode($response));
	}
}