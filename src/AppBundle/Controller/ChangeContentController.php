<?php

namespace AppBundle\Controller;

use AppBundle\Entity\ChangeContent;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/changes/content")
 * @Security("has_role('ROLE_ADMIN')")
 */
class ChangeContentController extends Controller {
	/**
	 * @param ChangeContent $content
	 * @return Response
	 *
	 * @Route("/diff/{id}", name="ajax_changecontent_diff")
	 * @Method("GET")
	 */
	public function diffAction(ChangeContent $content) {
		/** @var $content ChangeContent */
		$lines = explode("\n", $content->getPatch());
		$newLines_left = [];
		$newLines_right = [];
		$newLines_spacers = [];
		$start_line = [];
		foreach ($lines as $line) {
			if (preg_match('/^\@\@ -(\d+),\d+ \+\d+,\d+ \@\@/', $line, $match)) {
				$st_ln_num = $match[1];
				$start_line['original'] = $st_ln_num;
				$start_line['left'] = $st_ln_num;
				$start_line['right'] = $st_ln_num;
				$line = str_replace($match[0], '', $line);
				$line = trim($line);
				$newLines_spacers[] = $st_ln_num+1;
				if(empty($line)) {
					continue;
				}
			}
			$char = strlen($line) ? $line[0] : '~';
			$line = ltrim($line, '+-');
			switch ($char) {
				case '-':
					$line_left = ++$start_line['left'];
					$type = 'removed';
					$newLines_left[$line_left] = ['type' => $type, 'content' => $line];
					break;
				case '+':
					$line_right = ++$start_line['right'];
					$type = 'added';
					$newLines_right[$line_right] = ['type' => $type, 'content' => $line];
					break;
				default:
					$line_left = ++$start_line['left'];
					$line_right = ++$start_line['right'];
					$type = 'neutral';
					$newLines_left[$line_left] = ['type' => $type, 'content' => $line];
					$newLines_right[$line_right] = ['type' => $type, 'content' => $line];
					break;
			}
		} // end foreach
		//handle data
		return $this->render('home/diff.html.twig', [
			'content' => $content,
			'newLines_left' => $newLines_left,
			'newLines_right' => $newLines_right,
			'newLines_spacers' => $newLines_spacers
		]);
	}
	/**
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 *
	 * @Route("/details", name="ajax_change_details")
	 * @Method({"GET", "POST"})
	 */
	public function showAction(Request $request) {
		$response = ['status' => true, 'message' => ''];
		$change_id = $request->get('change_id');
		$translator = $this->get('translator');
		
		if($request->getMethod() == 'POST') {
			/** @var EntityManager $em */
			$em = $this->getDoctrine()->getManager();
			$changeRepo = $em->getRepository(Change::class);
			$change = $changeRepo->find($change_id);
			$newEditedTitle = $request->get('edited_title');
			if($change->getEditedTitle() != $newEditedTitle) {
				$change->setEditedTitle($newEditedTitle);
				$em->persist($change);
				$em->flush();
				$response['message'] = 'test';
			}
		}else{
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
					['content' => $translator->trans('status.'.$content->getStatus()), 'class' => 'text-center table-'.$content->getCssStatus()],
					['content' => $content->getAdditions(), 'class' => 'text-center'],
					['content' => $content->getChanges(), 'class' => 'text-center'],
					['content' => $content->getDeletions(), 'class' => 'text-center']
				];
			}
			
			$response['body_data'] = $body_data;
			$response['options'] = $rewatajax->getOptions();
		}
		//handle data
		return new Response(json_encode($response), 200, ['content-type' => 'text/json']);
	}
}
