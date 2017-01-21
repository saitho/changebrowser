<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Change;
use AppBundle\Entity\ChangeContent;
use AppBundle\Entity\Project;
use AppBundle\Helper\ReWatajaxDoctrine;
use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/changes")
 * @Security("has_role('ROLE_ADMIN')")
 */
class ChangeController extends Controller {
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
	 * @Route("/list", name="ajax_loadProject")
	 * @Method("GET")
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function showForProjectAction(Request $request) {
		$response = ['status' => false, 'message' => ''];
		$project_id = $request->get('project_id');
				
		$project = null;
		try {
			$project = $this->getProjectFromId($project_id);
			$response['status'] = true;
		} catch(\Exception $e) {
			$response = ['status' => false, 'message' => $e->getMessage()];
		}
		if($response['status']) {
			$translator = $this->get('translator');
			
			$response['header'] = [
				'type' => [
					'content' => '',
					'transform' => '<span class="badge badge-!CSSClassForType">!_self</span>',
					'width' => '5%'
				],
				'title' => [
					'content' => $translator->trans('label.title'),
					'sortable' => true
				],
				'author' => [
					'content' => $translator->trans('label.author'),
					'sortable' => true,
					'width' => '15%'
				],
				'date' => [
					'transform' => 'date',
					'content' => $translator->trans('label.date'),
					'sortable' => true,
					'width' => '15%'
				],
				'version' => [
					'content' => $translator->trans('label.version'),
					'sortable' => true,
					'width' => '5%'
				],
				'actions' => [
					'virtual' => true,
					'content' => '',
					'transform' => '<a href="javascript:toggleDetails(\'!id\');" '.
						'class="pull-right btn btn-xs btn-primary">'.
						'<i id="dropDown-activator-!id" class="fa fa-angle-down"></i>'.
						'</a>',
					'width' => '5%'
				]
			];
			/** @var EntityManager $em */
			$em = $this->getDoctrine()->getManager();
			$rewatajax = new ReWatajaxDoctrine($em);
			$rewatajax->setHeaderConfiguration($response['header']);
					
			$options = ['sortedBy' => 'date', 'sortMode' => 'DESC'];
			if($request->get('sort_by')) {
				$options['sortedBy'] = $request->get('sort_by');
			}
			if($request->get('sort_mode')) {
				$options['sortMode'] = $request->get('sort_mode');
			}
			if($request->get('current_page')) {
				$options['current_page'] = $request->get('current_page');
			}else{
				$options['current_page'] = 1;
			}
			if($request->get('per_page')) {
				$options['per_page'] = $request->get('per_page');
			}
			if($request->get('search')) {
				$options['search'] = $request->get('search');
			}
			$rewatajax->setOptions($options);
			
			$rewatajax->setTable('AppBundle:Change');
			$rewatajax->setWhere('a.project = :project');
			$rewatajax->setParams(['project' => $project]);
			
			$result = $rewatajax->findResults();
			$changes = [];
			/** @var Change $change */
			foreach($result AS $change) {
				$changeContents = [];
				/** @var ChangeContent $content */
				foreach($change->getChangeContents() AS $content) {
					$std = new \stdClass();
					$std->columns = [
						$content->getFilename(),
						['content' => $content->getStatus(), 'class' => 'text-center table-'.$content->getCssStatus()],
						['content' => $content->getAdditions(), 'class' => 'text-center'],
						['content' => $content->getChanges(), 'class' => 'text-center'],
						['content' => $content->getDeletions(), 'class' => 'text-center'],
						[
							'content' => '<a target="_blank" href="'.$this->generateUrl('ajax_changecontent_diff', ['id' => $content->getId()]).'" class="btn btn-primary btn-sm">'.$translator->trans('label.details').'</a>',
							'class' => 'text-right'
						],
					];
					$changeContents[] = $std;
				}
				$changes[] = [
					'id' => $change->getId(),
					'author' => $change->getAuthor(),
					'date' => $change->getDate(),
					'version' => $change->getVersion(),
					'title' => htmlentities($change->getTitle()),
					'type' => $change->getType(),
					'CSSClassForType' => $change->getCSSClassForType(),
					'changeContents_head' => [
						$translator->trans('label.filename'),
						[
							'content' => $translator->trans('label.status'),
							'width' => '10%',
							'class' => 'text-center'
						],
						[
							'content' => $translator->trans('label.changecontent.additions'),
							'width' => '5%',
							'class' => 'text-center'
						],
						[
							'content' => $translator->trans('label.changecontent.deletions'),
							'width' => '5%',
							'class' => 'text-center'
						],
						[
							'content' => $translator->trans('label.changecontent.changes'),
							'width' => '5%',
							'class' => 'text-center'
						],
						[
							'content' => $translator->trans('label.actions'),
							'width' => '20%',
							'class' => 'text-right'
						]
					],
					'changeContents_content' => $changeContents
				];
			}
			
			$response['changes'] = $changes;
			$response['options'] = $rewatajax->getOptions();
		}
		//handle data
		return new Response(json_encode($response));
	}
}
