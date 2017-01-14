<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Project;
use AppBundle\Entity\Source\AbstractSource;
use AppBundle\Form\ProjectType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/project")
 * @Security("has_role('ROLE_ADMIN')")
 */
class ProjectController extends Controller {
	
	/**
	 * @param int $project_id
	 * @return \AppBundle\Entity\Project
	 * @throws \Exception
	 */
	private function getProjectFromId($project_id=0) {
		if(empty($project_id)) {
			throw new \Exception($this->get('translator')->trans('Missing Project ID'));
		}
		$projectRepo = $this->getDoctrine()->getRepository(Project::class);
		/** @var Project $project */
		$project = $projectRepo->find($project_id);
		if(empty($project)) {
			throw new \Exception($this->get('translator')->trans('Project not found'));
		}
		return $project;
	}
	/**
	 * @Route("/details", name="ajax_project_details")
	 * @Method("POST")
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function detailsAction(Request $request) {
		$project_id = $request->request->get('project_id');
		try {
			$project = $this->getProjectFromId($project_id);
			
			$content = $this->get('twig')->render(':project:details.html.twig', array('project' => $project));
			$response = ['status' => true, 'modal' => [
				'header' => $project->getTitle(),
				'content' => $content
			]];
		} catch(\Exception $e) {
			$response = ['status' => false, 'message' => $e->getMessage()];
		}
		
		return new Response(json_encode($response));
	}
	/**
	 * @Route("/add", name="ajax_project_add")
	 * @Method({"GET", "POST"})
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function createAction(Request $request) {
		$project = new Project();
		// See http://symfony.com/doc/current/book/forms.html#submitting-forms-with-multiple-buttons
		$form = $this->createForm(ProjectType::class, $project);
		
		$form->handleRequest($request);
		
		if ($form->isSubmitted()) {
			if($form->isValid()) {
				$entityManager = $this->getDoctrine()->getManager();
				$entityManager->persist($project);
				$entityManager->flush();
				$response = ['status' => true];
			}else{
				$response = ['status' => false, 'message' => 'invalid_form'];
			}
		}else{
			$additionalFields = [];
			$sourceRepo = $this->getDoctrine()->getRepository(AbstractSource::class);
			$sources = $sourceRepo->findAll();
			foreach($sources AS $source) {
				$additionalFields[$source->getId()] = $source->getOptions();
			}
			$content = $this->get('twig')->render(
				':project:create.html.twig',
				[
					'form' => $form->createView(),
					'additionalFields' => $additionalFields
				]
			);
			$response = [
				'status' => true,
				'modal' => [
					'header' => $this->get('translator')->trans('title.project_new'),
					'content' => $content
				]
			];
		}
		
		return new Response(json_encode($response));
	}
}
