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
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 *
	 * @Route("/details", name="ajax_project_details")
	 * @Method("GET")
	 */
	public function detailsAction(Request $request) {
		$project_id = $request->get('project_id');
		try {
			if(empty($project_id)) {
				throw new \Exception($this->get('translator')->trans('Missing Project ID'));
			}
			$projectRepo = $this->getDoctrine()->getRepository(Project::class);
			/** @var Project $project */
			$project = $projectRepo->find($project_id);
			if(empty($project)) {
				throw new \Exception($this->get('translator')->trans('Project not found'));
			}
			
			$content = $this->get('twig')->render(':project:details.html.twig', array('project' => $project));
			$response = ['status' => true, 'modal' => [
				'header' => $project->getTitle(),
				'content' => $content
			]];
		} catch(\Exception $e) {
			$response = ['status' => false, 'message' => $e->getMessage()];
		}
		
		return new Response(json_encode($response), 200, ['content-type' => 'text/json']);
	}
	
	/**
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 *
	 * @Route("/add", name="ajax_project_add")
	 * @Method({"GET", "POST"})
	 */
	public function createAction(Request $request) {
		try {
			$project = new Project();
			// See http://symfony.com/doc/current/book/forms.html#submitting-forms-with-multiple-buttons
			$form = $this->createForm(ProjectType::class, $project);
			
			if ($request->getMethod() === 'POST') {
				parse_str($request->request->get('formData'), $formData);
				$submittedData = $formData['project'];
				
				// Set options separately
				$project->setOptions($submittedData['options']);
				unset($submittedData['options']);
				
				// Check if mandatory fields were posted (should be done by Symfony?!)
				if(empty($submittedData['title']) || empty($submittedData['source'])) {
					throw new \Exception('missing_fields');
				}
				
				$form->submit($submittedData);
				if($form->isValid()) {
					$entityManager = $this->getDoctrine()->getManager();
					$entityManager->persist($project);
					$entityManager->flush();
					$response = ['status' => true, 'id' => $project->getId(), 'title' => $project->getTitle()];
				}else{
					throw new \Exception('invalid_form');
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
		} catch(\Throwable $e) {
			$response = [
				'status' => false,
				'message' => $e->getMessage()
			];
		}
		
		return new Response(json_encode($response), 200, ['content-type' => 'text/json']);
	}
}
