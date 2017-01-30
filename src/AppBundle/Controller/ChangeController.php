<?php

namespace AppBundle\Controller;

use AppBundle\DBAL\EnumChangeTypeType;
use AppBundle\Entity\Change;
use AppBundle\Entity\ChangeContent;
use AppBundle\Entity\Project;
use AppBundle\Helper\ReWatajaxDoctrine;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
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
					'transform' => '<span class="badge badge-tag-!plainType">!_self</span>',
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
					'type' => 'date',
					'sortable' => true,
					'filterable' => true,
					'content' => $translator->trans('label.date'),
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
					'transform' => '<button class="pull-right btn btn-xs btn-primary changeDetailsButton" data-id="!id">'.
						'<i class="fa fa-info-circle"></i>'.
						'</button>',
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
			if($request->get('filter')) {
				$options['filter'] = $request->get('filter');
			}
			$rewatajax->setOptions($options);
			
			$rewatajax->setTable('AppBundle:Change');
			$rewatajax->setWhere('a.project = :project');
			$rewatajax->setParams(['project' => $project]);
			
			$result = $rewatajax->findResults();
			$body_data = [];
			/** @var Change $change */
			foreach($result AS $change) {
				$changeContents = [];
				/** @var ChangeContent $content */
				foreach($change->getChangeContents() AS $content) {
					$std = new \stdClass();
					$std->columns = [
						['content' => $content->getFilename().' <a target="_blank" href="'.
							$this->generateUrl('ajax_changecontent_diff', ['id' => $content->getId()]).
							'" class="btn btn-primary btn-sm">'.$translator->trans('label.diff').'</a>'],
						['content' => $translator->trans('status.'.$content->getStatus().''), 'class' => 'text-center table-'.$content->getCssStatus()],
						['content' => $content->getAdditions(), 'class' => 'text-center'],
						['content' => $content->getChanges(), 'class' => 'text-center'],
						['content' => $content->getDeletions(), 'class' => 'text-center']
					];
					$changeContents[] = $std;
				}
				$body_data[] = [
					'id' => $change->getId(),
					'author' => $change->getAuthor(),
					'date' => $change->getDate(),
					'version' => $change->getVersion(),
					'title' => htmlentities($change->getTitle()),
					'plainType' => $change->getType(),
					'type' => $translator->trans('tag.'.$change->getType()),
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
						]
					],
					'changeContents_content' => $changeContents
				];
			}
			
			$response['body_data'] = $body_data;
			$response['options'] = $rewatajax->getOptions();
			
			// Statistics
			// can't re-use QueryBuilder and Query from ReWatajax (does not return anything...!?)
			// therefore set filter and search again here...
			/** @var QueryBuilder $qb */
			$em = $this->getDoctrine()->getManager();
			$qb = $em->createQueryBuilder();
			$qb->select('count(a.id) AS results, YEAR(a.date) AS year, MONTH(a.date) AS month, DAY(a.date) AS day, a.date')
				->from('AppBundle:Change', 'a')
				->where('a.project = :project')
				->groupBy('year')
				->addGroupBy('month')
				->addGroupBy('day');
			
			$query = $em->createQuery();
			$query->setParameter('project', $project);
			if(!empty($options['search'])) {
				$orX = $qb->expr()->orX();
				foreach($response['header'] AS $k => $v) {
					if(!empty($v['virtual'])) {
						continue;
					}
					$orX->add('a.'.$k.' LIKE :search');
				}
				$qb->andWhere($orX);
				$query->setParameter('search', '%'.$options['search'].'%');
			}
			if(!empty($options['filter'])) {
				foreach ($options['filter'] AS $filter) {
					if($filter['filterType'] != 'datetime') {
						continue;
					}
					foreach($filter['filterOptions'] AS $filterOption) {
						$filterKey = $filterOption['filterKey'];
						// 0 = start; 1 = end
						if(count($filterOption['filterValues']) >= 1) {
							$startDate = new \DateTime($filterOption['filterValues'][0]);
							$startDate->setTime(0, 0, 0);
							$qb->andWhere('a.'.$filterKey . ' >= :' . $filterKey . '_startDate');
							$query->setParameter($filterKey . '_startDate', $startDate);
						}
						if(count($filterOption['filterValues']) >= 2) {
							$endDate = new \DateTime($filterOption['filterValues'][1]);
							$endDate->setTime(23, 59, 59);
							$qb->andWhere('a.'.$filterKey.' <= :'.$filterKey.'_endDate');
							$query->setParameter($filterKey.'_endDate', $endDate);
						}
					}
				}
			}
			
			$tags = EnumChangeTypeType::$values;
			$dateArray = [];
			$statistics = [];
			$originalQb = $qb;
			/** @var ArrayCollection $queryParams */
			$queryParams = $query->getParameters();
			foreach($tags AS $tag) {
				$queryParamsCopy = clone $queryParams;
				$qb = clone $originalQb;
				if(empty($tag)) {
					$qb->andWhere('a.type IS NULL');
					$tag = 'undefined';
				}else{
					$queryParamsCopy->add(new Parameter('type', $tag));
					$qb->andWhere('a.type = :type');
				}
				$query->setParameters($queryParamsCopy);
				$query->setDQL($qb->getDQL());
				$results = $query->execute();
				foreach($results AS $result) {
					if($result['results'] == 0) {
						continue;
					}
					/** @var \DateTime $date */
					$date = $result['date'];
					$formattedDate = $date->format('Y-m-d');
					$statistics[$tag][$formattedDate] = intval($result['results']);
					if(!in_array($formattedDate, $dateArray)) {
						$dateArray[] = $formattedDate;
					}
				}
			}
			
				// add todays date if not already added
			// $date = new \DateTime();
			// $dateFormatted = $date->format('Y-m-d');
			// if(!in_array($dateFormatted, $dateArray)) {
			// 	$dateArray[] = $dateFormatted;
			// }
			sort($dateArray);
			
			// but we have to complete the other tags as they might be shorter
			foreach($statistics AS &$statistic) {
				$arrayDiff = array_diff($dateArray, array_keys($statistic));
				foreach ($arrayDiff AS $key) {
					$statistic[$key] = 0;
				}
				ksort($statistic);
			}
			
			$response['statistics'] = [
				'datasets' => $statistics,
				'xAxisLabels' => $dateArray
			];
			$response['flags'] = [
				'hasChanges' => $project->hasChanges(),
				'hasCompleteChanges' => $project->hasCompleteChanges()
			];
		}
		//handle data
		return new Response(json_encode($response), 200, ['content-type' => 'text/json']);
	}
	/**
	 * @Route("/details", name="ajax_change_details")
	 * @Method("GET")
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function changeDetailsAction(Request $request) {
		$response = ['status' => true, 'message' => ''];
		$change_id = $request->get('change_id');
		
		$translator = $this->get('translator');
		
		$response['header'] = [
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
			]
		];
		/** @var EntityManager $em */
		$em = $this->getDoctrine()->getManager();
		$rewatajax = new ReWatajaxDoctrine($em);
		$rewatajax->setHeaderConfiguration($response['header']);
		
		$rewatajax->setTable('AppBundle:ChangeContent');
		$rewatajax->setWhere('a.change = :change');
		$rewatajax->setParams(['change' => $change_id]);
		
		$result = $rewatajax->findResults();
		$body_data = [];
		/** @var ChangeContent $content */
		foreach($result AS $content) {
			$body_data[] = [
				['content' => $content->getFilename().' <a target="_blank" href="'.
					$this->generateUrl('ajax_changecontent_diff', ['id' => $content->getId()]).
					'" class="btn btn-primary btn-sm">'.$translator->trans('label.diff').'</a>'],
				['content' => $translator->trans('status.'.$content->getStatus().''), 'class' => 'text-center table-'.$content->getCssStatus()],
				['content' => $content->getAdditions(), 'class' => 'text-center'],
				['content' => $content->getChanges(), 'class' => 'text-center'],
				['content' => $content->getDeletions(), 'class' => 'text-center']
			];
		}
		
		$response['body_data'] = $body_data;
		$response['options'] = $rewatajax->getOptions();
		//handle data
		return new Response(json_encode($response), 200, ['content-type' => 'text/json']);
	}
}
