<?php

namespace AppBundle\Controller;

use AppBundle\DBAL\EnumChangeTypeType;
use AppBundle\Entity\Project;
use AppBundle\Helper\ChangelogExporter;
use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/exporter")
 * @Security("has_role('ROLE_ADMIN')")
 */
class ExporterController extends Controller {
	/**
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @throws \Exception
	 *
	 * @Route("/", name="ajax_change_export")
	 * @Method({"GET", "POST"})
	 */
	public function exportChangesAction(Request $request) {
		$response = ['status' => false, 'message' => ''];
		$project_id = $request->get('project_id');
				
		$project = null;
		try {
			$projectRepo = $this->getDoctrine()->getManager()->getRepository(Project::class);
			$project = $projectRepo->find($project_id);
			$response['status'] = true;
		} catch(\Exception $e) {
			$response = ['status' => false, 'message' => $e->getMessage()];
		}
		
		if ($request->get('form_submit') == 'true') {
			if(empty($request->get('format'))) {
				$response['message'] = 'Missing format.';
				return new Response(json_encode($response), 200, ['content-type' => 'text/json']);
			}
			if(empty($request->get('tags'))) {
				$response['message'] = 'No tags selected.';
				return new Response(json_encode($response), 200, ['content-type' => 'text/json']);
			}
			if(empty($request->get('versions'))) {
				$response['message'] = 'No versions selected.';
				return new Response(json_encode($response), 200, ['content-type' => 'text/json']);
			}
			
			$exporter = new ChangelogExporter(
				$this->get('translator'),
				$this->getDoctrine()->getManager(),
				$project,
				$request->get('format'),
				$request->get('tags'),
				$request->get('versions')
			);
			$exporter->startDownload();
			die;
		}elseif($response['status']) {
			// Find versions
			/** @var EntityManager $em */
			$em = $this->getDoctrine()->getManager();
			$qb = $em->createQueryBuilder();
			$qb->select('a.version')
				->from('AppBundle:Change', 'a')
				->where('a.project = :project')
				->groupBy('a.version')
				->orderBy('a.version', 'DESC');
			$query = $em->createQuery($qb->getDQL());
			$query->setParameter('project', $project);
			
			$versionArray = [];
			$result = $query->execute();
			foreach($result AS $item) {
				$versionArray[] = $item['version'];
			}
			
			$response['modal'] = [
				'header' => $this->get('translator')->trans('title.export_changes'),
				'content' => $this->get('twig')->render(
					'@App/changes/export.twig', [
						'project_id' => $project_id,
						'versions' => $versionArray,
						'tags' => EnumChangeTypeType::$values
					]
				)
			];
		}
		
		//handle data
		return new Response(json_encode($response), 200, ['content-type' => 'text/json']);
	}
}
